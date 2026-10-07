"""
Good Property & Deal Finder Engine.
Identifies underpriced listings and ranks deals by discount vs district median $/m².
"""

import pandas as pd
from typing import Optional, Dict, Any
from database import get_connection, DB_PATH
from config import DEAL_DISCOUNT_THRESHOLD_GOOD, DEAL_DISCOUNT_THRESHOLD_HOT


def find_good_deals(
    province: Optional[str] = None,
    property_type: Optional[str] = None,
    listing_type: str = "Sale",
    min_discount_pct: float = 15.0,
    min_area: Optional[float] = None,
    max_price: Optional[float] = None,
    include_urgent_tags: bool = True,
    limit: int = 50,
    db_path: str = DB_PATH
) -> pd.DataFrame:
    """
    Find listings priced substantially below the district/province median $/m².
    Strictly filters by listing_type ('Sale' or 'Rent') to avoid comparing rental rates with purchase prices.
    """
    with get_connection(db_path) as conn:
        # Load all valid listings for the specified listing_type
        query = """
            SELECT id, source, title, property_type, listing_type, price_usd, area_sqm, price_per_sqm,
                   province, district, address, url, image_url, urgency_tag, scraped_at
            FROM properties
            WHERE listing_type = ?
              AND price_per_sqm > 0
              AND price_usd > 0
              AND area_sqm > 0
        """
        params = [listing_type]
        if province and province != "All":
            query += " AND province LIKE ?"
            params.append(f"%{province}%")
        if property_type and property_type != "All":
            query += " AND property_type = ?"
            params.append(property_type)
        if min_area and min_area > 0:
            query += " AND area_sqm >= ?"
            params.append(min_area)
        if max_price and max_price > 0:
            query += " AND price_usd <= ?"
            params.append(max_price)

        df = pd.read_sql_query(query, conn, params=params)

    if df.empty or len(df) < 2:
        return pd.DataFrame()

    # Create location grouping key: (province, district, property_type)
    # If district is empty, fall back to (province, property_type)
    df["group_key"] = df["province"].fillna("") + " | " + df["district"].fillna("") + " | " + df["property_type"].fillna("")
    df["broad_group_key"] = df["province"].fillna("") + " | " + df["property_type"].fillna("")

    # Calculate group medians
    group_medians = df.groupby("group_key")["price_per_sqm"].median().to_dict()
    group_counts = df.groupby("group_key")["price_per_sqm"].count().to_dict()

    broad_medians = df.groupby("broad_group_key")["price_per_sqm"].median().to_dict()

    def get_benchmark_median(row):
        gk = row["group_key"]
        # If group has at least 3 items, use its median; otherwise broad median
        if group_counts.get(gk, 0) >= 3:
            return group_medians[gk]
        bk = row["broad_group_key"]
        return broad_medians.get(bk, row["price_per_sqm"])

    df["benchmark_median_pp_sqm"] = df.apply(get_benchmark_median, axis=1)

    # Compute discount percentage vs benchmark
    df["discount_pct"] = (
        (df["benchmark_median_pp_sqm"] - df["price_per_sqm"]) / df["benchmark_median_pp_sqm"]
    ) * 100.0

    # Categorize Deal Quality
    def tag_deal(row):
        disc = row["discount_pct"]
        urg = str(row["urgency_tag"] or "").lower()

        if disc >= 35.0:
            return "🚀 Deep Discount (>35%)"
        elif disc >= 20.0:
            return "🔥 Great Deal (20-35%)"
        elif disc >= 10.0:
            return "✨ Good Deal (10-20%)"
        elif any(w in urg for w in ["urgent", "under market", "below market"]):
            return "⚡ Urgent Seller Mark"
        elif disc >= -10.0:
            return "⚖️ Fair Market Price"
        else:
            return "⚠️ Premium / High"

    df["deal_rating"] = df.apply(tag_deal, axis=1)

    # Filter by minimum discount OR urgency tag
    if include_urgent_tags:
        filtered = df[
            (df["discount_pct"] >= min_discount_pct) |
            (df["urgency_tag"].str.len() > 0)
        ]
    else:
        filtered = df[df["discount_pct"] >= min_discount_pct]

    # Sort best deals first
    filtered = filtered.sort_values(by="discount_pct", ascending=False).head(limit)
    return filtered
