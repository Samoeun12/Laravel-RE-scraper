"""
Land Price Estimator & District Valuation Benchmark Engine.
Estimates land prices ($/m² and Total USD) across Cambodian provinces and districts.
"""

import pandas as pd
import numpy as np
from typing import Dict, Any, Optional
from database import get_connection, DB_PATH


def estimate_land_price(
    area_sqm: float,
    province: str = "Phnom Penh",
    district: Optional[str] = None,
    road_type: str = "Standard Road",
    db_path: str = DB_PATH
) -> Dict[str, Any]:
    """
    Estimate total land value and $/m² based on real scraped comps.
    """
    if area_sqm <= 0:
        return {"error": "Land area must be greater than 0"}

    with get_connection(db_path) as conn:
        # Load land listings strictly for Sale
        query = """
            SELECT id, source, title, price_usd, area_sqm, price_per_sqm,
                   province, district, address, url, urgency_tag, scraped_at
            FROM properties
            WHERE property_type = 'Land'
              AND listing_type = 'Sale'
              AND price_per_sqm > 0
              AND price_usd > 0
              AND area_sqm > 0
        """
        params = []
        if province and province != "All":
            query += " AND province LIKE ?"
            params.append(f"%{province}%")

        if district and district != "All":
            query += " AND district LIKE ?"
            params.append(f"%{district}%")

        df = pd.read_sql_query(query, conn, params=params)

        # Fallback to all sale properties in district/province if few land comps
        if len(df) < 3:
            fallback_query = """
                SELECT id, source, title, price_usd, area_sqm, price_per_sqm,
                       province, district, address, url, urgency_tag, scraped_at
                FROM properties
                WHERE listing_type = 'Sale'
                  AND price_per_sqm > 0 
                  AND price_usd > 0 
                  AND area_sqm > 0
            """
            fb_params = []
            if province and province != "All":
                fallback_query += " AND province LIKE ?"
                fb_params.append(f"%{province}%")
            df = pd.read_sql_query(fallback_query, conn, params=fb_params)

    if df.empty:
        # Fallback heuristic benchmarks for Cambodia if database is completely fresh
        base_rate = 1200.0 if "phnom" in province.lower() else 250.0
        return {
            "count": 0,
            "is_heuristic": True,
            "rates_pp_sqm": {
                "conservative": round(base_rate * 0.8, 2),
                "fair_market": round(base_rate, 2),
                "premium": round(base_rate * 1.3, 2),
            },
            "estimated_totals": {
                "conservative": round(base_rate * 0.8 * area_sqm, 2),
                "fair_market": round(base_rate * area_sqm, 2),
                "premium": round(base_rate * 1.3 * area_sqm, 2),
            },
            "comps_df": pd.DataFrame()
        }

    # Road / frontage multiplier adjustment
    road_multiplier = 1.0
    if road_type == "Main Blvd / Commercial Frontage":
        road_multiplier = 1.35
    elif road_type == "Secondary Road (8m-12m)":
        road_multiplier = 1.10
    elif road_type == "Sub-lane / Residential":
        road_multiplier = 0.90

    # Size-based economy of scale: large land (>5000m²) has lower $/m² than small plots
    size_factor = 1.0
    if area_sqm > 10000:
        size_factor = 0.80
    elif area_sqm > 3000:
        size_factor = 0.90
    elif area_sqm < 300:
        size_factor = 1.15

    rates = df["price_per_sqm"].dropna()
    p25 = float(np.percentile(rates, 25)) * road_multiplier * size_factor
    median = float(rates.median()) * road_multiplier * size_factor
    p75 = float(np.percentile(rates, 75)) * road_multiplier * size_factor

    return {
        "count": len(df),
        "is_heuristic": False,
        "rates_pp_sqm": {
            "conservative": round(p25, 2),
            "fair_market": round(median, 2),
            "premium": round(p75, 2),
        },
        "estimated_totals": {
            "conservative": round(p25 * area_sqm, 2),
            "fair_market": round(median * area_sqm, 2),
            "premium": round(p75 * area_sqm, 2),
        },
        "comps_df": df.sort_values(by="scraped_at", ascending=False).head(10)
    }


def get_district_land_benchmarks(province: str = "Phnom Penh", db_path: str = DB_PATH) -> pd.DataFrame:
    """
    Generate district-level summary statistics for Land prices ($/m²).
    """
    with get_connection(db_path) as conn:
        query = """
            SELECT 
                COALESCE(district, 'Unknown') as district,
                COUNT(*) as listing_count,
                ROUND(MIN(price_per_sqm), 2) as min_pp_sqm,
                ROUND(AVG(price_per_sqm), 2) as avg_pp_sqm,
                ROUND(MAX(price_per_sqm), 2) as max_pp_sqm
            FROM properties
            WHERE property_type = 'Land'
              AND listing_type = 'Sale'
              AND price_per_sqm > 0
              AND province LIKE ?
            GROUP BY district
            HAVING listing_count >= 1
            ORDER BY avg_pp_sqm DESC
        """
        df = pd.read_sql_query(query, conn, params=[f"%{province}%"])

    return df
