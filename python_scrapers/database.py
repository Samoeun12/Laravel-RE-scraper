"""
Database layer for Cambodia Real Estate Property listings using SQLite.
"""

import sqlite3
import pandas as pd
from datetime import datetime
from typing import List, Dict, Any, Optional
from config import DB_PATH
from scrapers.base_scraper import normalize_pricing_and_unit


def get_connection(db_path: str = DB_PATH) -> sqlite3.Connection:
    """Create and return a database connection with row factory."""
    conn = sqlite3.connect(db_path, check_same_thread=False)
    conn.row_factory = sqlite3.Row
    return conn


def init_db(db_path: str = DB_PATH):
    """Initialize database tables and indexes."""
    with get_connection(db_path) as conn:
        cursor = conn.cursor()
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS properties (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                source TEXT NOT NULL,
                source_id TEXT,
                title TEXT NOT NULL,
                property_type TEXT DEFAULT 'Other',
                listing_type TEXT DEFAULT 'Sale',
                price_usd REAL,
                area_sqm REAL,
                price_per_sqm REAL,
                province TEXT,
                district TEXT,
                commune TEXT,
                address TEXT,
                bedrooms INTEGER,
                bathrooms INTEGER,
                url TEXT UNIQUE,
                image_url TEXT,
                urgency_tag TEXT,
                latitude REAL,
                longitude REAL,
                raw_data TEXT,
                scraped_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        """)

        # Migration: Add latitude and longitude if table was created earlier
        cursor.execute("PRAGMA table_info(properties)")
        columns = [row["name"] for row in cursor.fetchall()]
        if "latitude" not in columns:
            cursor.execute("ALTER TABLE properties ADD COLUMN latitude REAL")
        if "longitude" not in columns:
            cursor.execute("ALTER TABLE properties ADD COLUMN longitude REAL")

        # Indexes for rapid query and filtering
        cursor.execute("CREATE INDEX IF NOT EXISTS idx_properties_source ON properties(source)")
        cursor.execute("CREATE INDEX IF NOT EXISTS idx_properties_type ON properties(property_type)")
        cursor.execute("CREATE INDEX IF NOT EXISTS idx_properties_province ON properties(province)")
        cursor.execute("CREATE INDEX IF NOT EXISTS idx_properties_district ON properties(district)")
        cursor.execute("CREATE INDEX IF NOT EXISTS idx_properties_price ON properties(price_usd)")
        cursor.execute("CREATE INDEX IF NOT EXISTS idx_properties_ppsqm ON properties(price_per_sqm)")
        cursor.execute("CREATE INDEX IF NOT EXISTS idx_properties_area ON properties(area_sqm)")
        cursor.execute("CREATE INDEX IF NOT EXISTS idx_properties_lat ON properties(latitude)")
        cursor.execute("CREATE INDEX IF NOT EXISTS idx_properties_lon ON properties(longitude)")

        cursor.execute("""
            CREATE TABLE IF NOT EXISTS scrape_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                source TEXT NOT NULL,
                status TEXT NOT NULL,
                items_found INTEGER DEFAULT 0,
                items_saved INTEGER DEFAULT 0,
                started_at TIMESTAMP,
                finished_at TIMESTAMP,
                error_message TEXT
            )
        """)
        conn.commit()


def upsert_property(item: Dict[str, Any], db_path: str = DB_PATH) -> bool:
    """
    Insert a property or update if URL or (source, source_id) already exists.
    Returns True if inserted/updated, False otherwise.
    """
    if not item.get("title") and not item.get("url"):
        return False

    with get_connection(db_path) as conn:
        cursor = conn.cursor()
        # Normalize pricing, unit rates, and listing type
        price = item.get("price_usd")
        area = item.get("area_sqm")
        ltype = item.get("listing_type", "Sale")
        title = item.get("title", "")
        ptype = item.get("property_type", "Other")
        desc = item.get("address", "")

        price, pp_sqm, ltype = normalize_pricing_and_unit(
            title=title,
            desc=desc,
            price=price,
            area=area,
            property_type=ptype,
            listing_type=ltype
        )

        now = datetime.utcnow().isoformat()

        cursor.execute("""
            INSERT INTO properties (
                source, source_id, title, property_type, listing_type,
                price_usd, area_sqm, price_per_sqm, province, district,
                commune, address, bedrooms, bathrooms, url, image_url,
                urgency_tag, latitude, longitude, scraped_at, updated_at
            ) VALUES (
                :source, :source_id, :title, :property_type, :listing_type,
                :price_usd, :area_sqm, :price_per_sqm, :province, :district,
                :commune, :address, :bedrooms, :bathrooms, :url, :image_url,
                :urgency_tag, :latitude, :longitude, :scraped_at, :updated_at
            )
            ON CONFLICT(url) DO UPDATE SET
                price_usd = excluded.price_usd,
                area_sqm = excluded.area_sqm,
                price_per_sqm = excluded.price_per_sqm,
                title = excluded.title,
                province = COALESCE(excluded.province, properties.province),
                district = COALESCE(excluded.district, properties.district),
                commune = COALESCE(excluded.commune, properties.commune),
                address = COALESCE(excluded.address, properties.address),
                image_url = COALESCE(excluded.image_url, properties.image_url),
                urgency_tag = COALESCE(excluded.urgency_tag, properties.urgency_tag),
                latitude = COALESCE(excluded.latitude, properties.latitude),
                longitude = COALESCE(excluded.longitude, properties.longitude),
                updated_at = excluded.updated_at
        """, {
            "source": item.get("source", "unknown"),
            "source_id": str(item.get("source_id", "")),
            "title": item.get("title", "Untitled Property"),
            "property_type": ptype,
            "listing_type": ltype,
            "price_usd": price,
            "area_sqm": area,
            "price_per_sqm": pp_sqm,
            "province": item.get("province", ""),
            "district": item.get("district", ""),
            "commune": item.get("commune", ""),
            "address": item.get("address", ""),
            "bedrooms": item.get("bedrooms"),
            "bathrooms": item.get("bathrooms"),
            "url": item.get("url"),
            "image_url": item.get("image_url", ""),
            "urgency_tag": item.get("urgency_tag", ""),
            "latitude": item.get("latitude"),
            "longitude": item.get("longitude"),
            "scraped_at": item.get("scraped_at", now),
            "updated_at": now
        })
        conn.commit()
        return True


def upsert_properties_batch(items: List[Dict[str, Any]], db_path: str = DB_PATH) -> int:
    """Save a list of property items to the database in a single transaction."""
    saved_count = 0
    with get_connection(db_path) as conn:
        for item in items:
            try:
                # Normalize pricing, unit rates, and listing type
                price = item.get("price_usd")
                area = item.get("area_sqm")
                ltype = item.get("listing_type", "Sale")
                title = item.get("title", "")
                ptype = item.get("property_type", "Other")
                desc = item.get("address", "")

                price, pp_sqm, ltype = normalize_pricing_and_unit(
                    title=title,
                    desc=desc,
                    price=price,
                    area=area,
                    property_type=ptype,
                    listing_type=ltype
                )

                now = datetime.utcnow().isoformat()
                cursor = conn.cursor()
                cursor.execute("""
                    INSERT INTO properties (
                        source, source_id, title, property_type, listing_type,
                        price_usd, area_sqm, price_per_sqm, province, district,
                        commune, address, bedrooms, bathrooms, url, image_url,
                        urgency_tag, latitude, longitude, scraped_at, updated_at
                    ) VALUES (
                        :source, :source_id, :title, :property_type, :listing_type,
                        :price_usd, :area_sqm, :price_per_sqm, :province, :district,
                        :commune, :address, :bedrooms, :bathrooms, :url, :image_url,
                        :urgency_tag, :latitude, :longitude, :scraped_at, :updated_at
                    )
                    ON CONFLICT(url) DO UPDATE SET
                        price_usd = excluded.price_usd,
                        area_sqm = excluded.area_sqm,
                        price_per_sqm = excluded.price_per_sqm,
                        title = excluded.title,
                        province = COALESCE(excluded.province, properties.province),
                        district = COALESCE(excluded.district, properties.district),
                        commune = COALESCE(excluded.commune, properties.commune),
                        address = COALESCE(excluded.address, properties.address),
                        image_url = COALESCE(excluded.image_url, properties.image_url),
                        urgency_tag = COALESCE(excluded.urgency_tag, properties.urgency_tag),
                        latitude = COALESCE(excluded.latitude, properties.latitude),
                        longitude = COALESCE(excluded.longitude, properties.longitude),
                        updated_at = excluded.updated_at
                """, {
                    "source": item.get("source", "unknown"),
                    "source_id": str(item.get("source_id", "")),
                    "title": item.get("title", "Untitled Property"),
                    "property_type": ptype,
                    "listing_type": ltype,
                    "price_usd": price,
                    "area_sqm": area,
                    "price_per_sqm": pp_sqm,
                    "province": item.get("province", ""),
                    "district": item.get("district", ""),
                    "commune": item.get("commune", ""),
                    "address": item.get("address", ""),
                    "bedrooms": item.get("bedrooms"),
                    "bathrooms": item.get("bathrooms"),
                    "url": item.get("url"),
                    "image_url": item.get("image_url", ""),
                    "urgency_tag": item.get("urgency_tag", ""),
                    "latitude": item.get("latitude"),
                    "longitude": item.get("longitude"),
                    "scraped_at": item.get("scraped_at", now),
                    "updated_at": now
                })
                saved_count += 1
            except Exception as e:
                print(f"[DB] Error upserting item {item.get('url')}: {e}")
        conn.commit()
    return saved_count


def query_properties(
    source: Optional[str] = None,
    property_type: Optional[str] = None,
    listing_type: Optional[str] = None,
    province: Optional[str] = None,
    district: Optional[str] = None,
    min_price: Optional[float] = None,
    max_price: Optional[float] = None,
    min_sqm: Optional[float] = None,
    max_sqm: Optional[float] = None,
    min_pp_sqm: Optional[float] = None,
    max_pp_sqm: Optional[float] = None,
    search_text: Optional[str] = None,
    urgency_only: bool = False,
    order_by: str = "id DESC",
    limit: int = 1000,
    db_path: str = DB_PATH
) -> pd.DataFrame:
    """Query properties with dynamic filters (including listing_type: Sale vs Rent) and return a pandas DataFrame."""
    conditions = []
    params = {}

    if source and source != "All":
        conditions.append("source = :source")
        params["source"] = source

    if property_type and property_type != "All":
        conditions.append("property_type = :property_type")
        params["property_type"] = property_type

    if listing_type and listing_type != "All":
        conditions.append("listing_type = :listing_type")
        params["listing_type"] = listing_type

    if province and province != "All":
        conditions.append("province LIKE :province")
        params["province"] = f"%{province}%"

    if district and district != "All":
        conditions.append("district LIKE :district")
        params["district"] = f"%{district}%"

    if min_price is not None and min_price > 0:
        conditions.append("price_usd >= :min_price")
        params["min_price"] = min_price

    if max_price is not None and max_price > 0:
        conditions.append("price_usd <= :max_price")
        params["max_price"] = max_price

    if min_sqm is not None and min_sqm > 0:
        conditions.append("area_sqm >= :min_sqm")
        params["min_sqm"] = min_sqm

    if max_sqm is not None and max_sqm > 0:
        conditions.append("area_sqm <= :max_sqm")
        params["max_sqm"] = max_sqm

    if min_pp_sqm is not None and min_pp_sqm > 0:
        conditions.append("price_per_sqm >= :min_pp_sqm")
        params["min_pp_sqm"] = min_pp_sqm

    if max_pp_sqm is not None and max_pp_sqm > 0:
        conditions.append("price_per_sqm <= :max_pp_sqm")
        params["max_pp_sqm"] = max_pp_sqm

    if search_text:
        conditions.append("(title LIKE :search OR address LIKE :search OR district LIKE :search)")
        params["search"] = f"%{search_text}%"

    if urgency_only:
        conditions.append("(urgency_tag IS NOT NULL AND urgency_tag != '')")

    where_clause = " WHERE " + " AND ".join(conditions) if conditions else ""
    sql = f"SELECT * FROM properties{where_clause} ORDER BY {order_by} LIMIT {limit}"

    with get_connection(db_path) as conn:
        df = pd.read_sql_query(sql, conn, params=params)
    return df


def get_summary_stats(db_path: str = DB_PATH) -> Dict[str, Any]:
    """Return key metrics and counts for the dashboard header, separated by Sale vs Rent."""
    with get_connection(db_path) as conn:
        cursor = conn.cursor()
        cursor.execute("SELECT COUNT(*) as total FROM properties")
        total = cursor.fetchone()["total"]

        cursor.execute("SELECT COUNT(DISTINCT source) as sources FROM properties")
        sources_count = cursor.fetchone()["sources"]

        # Sale specific metrics
        cursor.execute("""
            SELECT COUNT(*) as count, AVG(price_usd) as avg_price, AVG(price_per_sqm) as avg_pp_sqm 
            FROM properties 
            WHERE listing_type = 'Sale' AND price_usd > 0
        """)
        sale_row = cursor.fetchone()
        total_sale = sale_row["count"] or 0
        avg_sale_price = sale_row["avg_price"] or 0
        avg_sale_pp_sqm = sale_row["avg_pp_sqm"] or 0

        # Rent specific metrics
        cursor.execute("""
            SELECT COUNT(*) as count, AVG(price_usd) as avg_price, AVG(price_per_sqm) as avg_pp_sqm 
            FROM properties 
            WHERE listing_type = 'Rent' AND price_usd > 0
        """)
        rent_row = cursor.fetchone()
        total_rent = rent_row["count"] or 0
        avg_rent_price = rent_row["avg_price"] or 0
        avg_rent_pp_sqm = rent_row["avg_pp_sqm"] or 0

        cursor.execute("""
            SELECT source, COUNT(*) as count 
            FROM properties 
            GROUP BY source 
            ORDER BY count DESC
        """)
        by_source = {r["source"]: r["count"] for r in cursor.fetchall()}

        cursor.execute("""
            SELECT property_type, COUNT(*) as count 
            FROM properties 
            GROUP BY property_type 
            ORDER BY count DESC
        """)
        by_type = {r["property_type"]: r["count"] for r in cursor.fetchall()}

    return {
        "total_properties": total,
        "sources_count": sources_count,
        "total_sale": total_sale,
        "avg_sale_price": round(avg_sale_price, 2),
        "avg_sale_pp_sqm": round(avg_sale_pp_sqm, 2),
        "total_rent": total_rent,
        "avg_rent_price": round(avg_rent_price, 2),
        "avg_rent_pp_sqm": round(avg_rent_pp_sqm, 2),
        "by_source": by_source,
        "by_type": by_type
    }


def log_scrape_run(
    source: str,
    status: str,
    found: int,
    saved: int,
    started_at: str,
    finished_at: str,
    error: Optional[str] = None,
    db_path: str = DB_PATH
):
    """Save execution record to scrape_logs."""
    with get_connection(db_path) as conn:
        cursor = conn.cursor()
        cursor.execute("""
            INSERT INTO scrape_logs (source, status, items_found, items_saved, started_at, finished_at, error_message)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        """, (source, status, found, saved, started_at, finished_at, error))
        conn.commit()


def get_scrape_logs(limit: int = 50, source: Optional[str] = None, db_path: str = DB_PATH) -> pd.DataFrame:
    """Retrieve historical scrape execution logs."""
    sql = "SELECT id, source, status, items_found, items_saved, started_at, finished_at, error_message FROM scrape_logs"
    params = {}
    if source and source != "All":
        sql += " WHERE source = :source"
        params["source"] = source
    sql += " ORDER BY id DESC LIMIT :limit"
    params["limit"] = limit

    with get_connection(db_path) as conn:
        df = pd.read_sql_query(sql, conn, params=params)
    return df


def get_recent_properties(limit: int = 8, listing_type: Optional[str] = None, db_path: str = DB_PATH) -> pd.DataFrame:
    """Retrieve recently ingested properties with rich metadata for the Command Center ticker."""
    sql = "SELECT id, source, title, property_type, listing_type, price_usd, area_sqm, price_per_sqm, province, district, url, image_url, urgency_tag, scraped_at, updated_at FROM properties"
    params = {}
    if listing_type and listing_type != "All":
        sql += " WHERE listing_type = :ltype"
        params["ltype"] = listing_type
    sql += " ORDER BY id DESC LIMIT :limit"
    params["limit"] = limit

    with get_connection(db_path) as conn:
        df = pd.read_sql_query(sql, conn, params=params)
    return df


def get_database_telemetry(db_path: str = DB_PATH) -> Dict[str, Any]:
    """Retrieve system diagnostics, file sizes, and database health metrics."""
    import os
    file_size_mb = 0.0
    if os.path.exists(db_path):
        file_size_mb = round(os.path.getsize(db_path) / (1024 * 1024), 2)

    with get_connection(db_path) as conn:
        cursor = conn.cursor()
        cursor.execute("PRAGMA page_count;")
        page_count = cursor.fetchone()[0]
        cursor.execute("PRAGMA page_size;")
        page_size = cursor.fetchone()[0]
        cursor.execute("PRAGMA freelist_count;")
        freelist_count = cursor.fetchone()[0]

        cursor.execute("SELECT COUNT(*) FROM properties")
        total_props = cursor.fetchone()[0]

        cursor.execute("SELECT COUNT(*) FROM scrape_logs")
        total_logs = cursor.fetchone()[0]

        cursor.execute("""
            SELECT COUNT(*) - COUNT(DISTINCT url) as dup_urls
            FROM properties
            WHERE url IS NOT NULL AND url != ''
        """)
        dup_urls = cursor.fetchone()[0] or 0

        cursor.execute("SELECT MAX(updated_at) as last_update FROM properties")
        last_update = cursor.fetchone()[0] or "N/A"

    fragmentation_pct = round((freelist_count / max(page_count, 1)) * 100, 1)

    return {
        "db_path": db_path,
        "file_size_mb": file_size_mb,
        "total_properties": total_props,
        "total_logs": total_logs,
        "duplicate_urls": dup_urls,
        "last_update": last_update,
        "page_count": page_count,
        "page_size": page_size,
        "freelist_count": freelist_count,
        "fragmentation_pct": fragmentation_pct
    }


def vacuum_database(db_path: str = DB_PATH) -> Dict[str, Any]:
    """Reclaim unallocated disk space and rebuild internal B-tree indexes."""
    import os
    size_before = os.path.getsize(db_path) / (1024 * 1024) if os.path.exists(db_path) else 0.0

    conn = sqlite3.connect(db_path, isolation_level=None)
    try:
        conn.execute("VACUUM;")
        conn.execute("ANALYZE;")
        success = True
        err = None
    except Exception as e:
        success = False
        err = str(e)
    finally:
        conn.close()

    size_after = os.path.getsize(db_path) / (1024 * 1024) if os.path.exists(db_path) else 0.0
    reclaimed_mb = max(round(size_before - size_after, 2), 0.0)

    return {
        "success": success,
        "size_before_mb": round(size_before, 2),
        "size_after_mb": round(size_after, 2),
        "reclaimed_mb": reclaimed_mb,
        "error": err
    }


def cleanup_duplicate_properties(db_path: str = DB_PATH) -> int:
    """Removes redundant duplicate property rows keeping the latest version."""
    with get_connection(db_path) as conn:
        cursor = conn.cursor()
        cursor.execute("""
            DELETE FROM properties
            WHERE id NOT IN (
                SELECT MAX(id)
                FROM properties
                GROUP BY url
            ) AND url IS NOT NULL AND url != ''
        """)
        deleted_count = cursor.rowcount
        conn.commit()
    return max(deleted_count, 0)


# Initialize database automatically on module import
init_db()

