"""
Comparable Market Analysis (CMA) engine.
Computes benchmark statistics and finds most similar comparable properties.
"""

import pandas as pd
import numpy as np
from typing import Dict, Any, Optional
from database import get_connection, DB_PATH


# Strata-title high-rise unit types strictly excluded from CMA to preserve landed valuation accuracy
EXCLUDED_CMA_TYPES = ("Condo", "Apartment", "Studio", "Penthouse", "Loft")


def generate_cma_report(
    target_area_sqm: float,
    province: str = "Phnom Penh",
    district: Optional[str] = None,
    property_type: str = "Land",
    listing_type: str = "Sale",
    target_price_usd: Optional[float] = None,
    size_tolerance_pct: float = 0.30,
    min_comps: int = 3,
    db_path: str = DB_PATH
) -> Dict[str, Any]:
    """
    Generate a complete CMA report for a target property specification.
    Strictly isolates 'Sale' vs 'Rent' listings so rental rates never distort purchase valuations.
    Strictly excludes strata-title high-rise units (Condo, Apartment) from CMA comparisons.
    """
    if target_area_sqm <= 0:
        return {"error": "Target area must be greater than 0"}

    if property_type in EXCLUDED_CMA_TYPES:
        return {
            "error": f"{property_type}s are excluded from Comparable Market Analysis (CMA) because strata-title high-rise units cannot be accurately compared with landed property valuations.",
            "count": 0
        }

    min_area = target_area_sqm * (1.0 - size_tolerance_pct)
    max_area = target_area_sqm * (1.0 + size_tolerance_pct)

    with get_connection(db_path) as conn:
        # 1. First attempt: Strict filter (Listing Type + Province + District + Type + Size range, excluding strata units)
        query = """
            SELECT id, source, title, property_type, listing_type, price_usd, area_sqm, price_per_sqm,
                   province, district, address, url, image_url, urgency_tag, scraped_at
            FROM properties
            WHERE listing_type = ?
              AND price_per_sqm > 0
              AND price_usd > 0
              AND area_sqm > 0
              AND property_type NOT IN ('Condo', 'Apartment', 'Studio', 'Penthouse', 'Loft')
        """
        params = [listing_type]

        if province and province != "All":
            query += " AND province LIKE ?"
            params.append(f"%{province}%")

        if district and district != "All":
            query += " AND district LIKE ?"
            params.append(f"%{district}%")

        if property_type and property_type != "All":
            query += " AND property_type = ?"
            params.append(property_type)

        query += " AND area_sqm BETWEEN ? AND ?"
        params.extend([min_area, max_area])

        df = pd.read_sql_query(query, conn, params=params)

        # 2. If insufficient comps found, widen search (relax size range or district, but keep listing_type and exclude strata)
        is_widened = False
        if len(df) < min_comps:
            is_widened = True
            fallback_query = """
                SELECT id, source, title, property_type, listing_type, price_usd, area_sqm, price_per_sqm,
                       province, district, address, url, image_url, urgency_tag, scraped_at
                FROM properties
                WHERE listing_type = ?
                  AND price_per_sqm > 0
                  AND price_usd > 0
                  AND area_sqm > 0
                  AND property_type NOT IN ('Condo', 'Apartment', 'Studio', 'Penthouse', 'Loft')
            """
            fb_params = [listing_type]
            if province and province != "All":
                fallback_query += " AND province LIKE ?"
                fb_params.append(f"%{province}%")
            if property_type and property_type != "All":
                fallback_query += " AND property_type = ?"
                fb_params.append(property_type)

            df = pd.read_sql_query(fallback_query, conn, params=fb_params)

    if df.empty:
        return {
            "count": 0,
            "message": "No comparable properties found in database matching criteria. Try running the scrapers to collect more listings."
        }

    # Calculate similarity score: based on area closeness and location match
    def calc_similarity(row):
        score = 100.0
        # Area delta penalty (up to 30 points)
        area_diff_ratio = abs(row["area_sqm"] - target_area_sqm) / target_area_sqm
        score -= min(area_diff_ratio * 50, 40)

        # District match bonus
        if district and district != "All" and str(row["district"]).lower() == district.lower():
            score += 15

        return max(round(score, 1), 10.0)

    df["similarity_score"] = df.apply(calc_similarity, axis=1)
    df = df.sort_values(by="similarity_score", ascending=False)

    # Statistical metrics for Price per sqm
    pp_sqm_series = df["price_per_sqm"].dropna()
    min_pp_sqm = float(pp_sqm_series.min())
    max_pp_sqm = float(pp_sqm_series.max())
    p25_pp_sqm = float(np.percentile(pp_sqm_series, 25))
    median_pp_sqm = float(pp_sqm_series.median())
    avg_pp_sqm = float(pp_sqm_series.mean())
    p75_pp_sqm = float(np.percentile(pp_sqm_series, 75))

    # Valuations based on target area
    val_conservative = round(p25_pp_sqm * target_area_sqm, 2)
    val_fair_market = round(median_pp_sqm * target_area_sqm, 2)
    val_premium = round(p75_pp_sqm * target_area_sqm, 2)

    # Variance analysis if target price is supplied
    variance = None
    if target_price_usd and target_price_usd > 0:
        target_pp_sqm = target_price_usd / target_area_sqm
        diff_pct = ((target_price_usd - val_fair_market) / val_fair_market) * 100
        status = "Below Market" if diff_pct < -5 else ("Above Market" if diff_pct > 5 else "Fair Value")
        variance = {
            "target_price": target_price_usd,
            "target_pp_sqm": round(target_pp_sqm, 2),
            "diff_pct": round(diff_pct, 1),
            "status": status
        }

    return {
        "count": len(df),
        "listing_type": listing_type,
        "is_widened": is_widened,
        "metrics": {
            "min_pp_sqm": round(min_pp_sqm, 2),
            "p25_pp_sqm": round(p25_pp_sqm, 2),
            "median_pp_sqm": round(median_pp_sqm, 2),
            "avg_pp_sqm": round(avg_pp_sqm, 2),
            "p75_pp_sqm": round(p75_pp_sqm, 2),
            "max_pp_sqm": round(max_pp_sqm, 2),
        },
        "valuation": {
            "conservative_usd": val_conservative,
            "fair_market_usd": val_fair_market,
            "premium_usd": val_premium,
        },
        "variance": variance,
        "comps_df": df.head(15)
    }
