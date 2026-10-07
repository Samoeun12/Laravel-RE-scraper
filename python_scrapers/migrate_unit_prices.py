"""
Migration script to fix unit prices ($/m²) and misclassified listing types in properties.db.
Resolves Khmer24 post 13115074 (ID 5572) and all similar listings.
"""

import sqlite3
import sys
from scrapers.base_scraper import normalize_pricing_and_unit, normalize_listing_type

sys.stdout.reconfigure(encoding='utf-8')

def run_migration(db_path: str = "properties.db"):
    conn = sqlite3.connect(db_path)
    cur = conn.cursor()

    cur.execute("""
        SELECT id, source, source_id, title, address, property_type, listing_type, price_usd, area_sqm, price_per_sqm
        FROM properties
    """)
    rows = cur.fetchall()
    print(f"Loaded {len(rows)} listings from database.")

    rent_converted = 0
    unit_price_fixed = 0
    updates = []

    for r in rows:
        pid, src, sid, title, addr, ptype, ltype, price, area, pp = r
        desc = addr or ""

        # Check listing type change
        new_ltype = normalize_listing_type(title, desc, ltype)
        if new_ltype != ltype:
            rent_converted += 1

        # Check price and unit rate
        new_price, new_pp, final_ltype = normalize_pricing_and_unit(
            title=title,
            desc=desc,
            price=price,
            area=area,
            property_type=ptype,
            listing_type=new_ltype
        )

        changed = False
        if final_ltype != ltype:
            changed = True
        if new_price is not None and price is not None and abs(new_price - price) > 0.01:
            changed = True
            unit_price_fixed += 1
        if new_pp is not None and (pp is None or abs(new_pp - pp) > 0.01):
            changed = True

        if changed:
            updates.append((new_price, new_pp, final_ltype, pid))

    print(f"\nDiscovered {len(updates)} records needing updates:")
    print(f" - Misclassified rentals reclassified to Rent: {rent_converted}")
    print(f" - Unit prices converted to total price ($/m² -> Total $): {unit_price_fixed}")

    # Apply updates in batch
    cur.executemany("""
        UPDATE properties
        SET price_usd = ?,
            price_per_sqm = ?,
            listing_type = ?
        WHERE id = ?
    """, updates)
    conn.commit()

    # Specifically check post 13115074
    cur.execute("""
        SELECT id, source, source_id, title, property_type, listing_type, price_usd, area_sqm, price_per_sqm
        FROM properties
        WHERE id = 5572 OR source_id = '13115074'
    """)
    p13115074 = cur.fetchone()
    print("\n--- Verification for Post 13115074 ---")
    if p13115074:
        print(f"ID: {p13115074[0]}")
        print(f"Source: {p13115074[1]}")
        print(f"Source ID: {p13115074[2]}")
        print(f"Title: {p13115074[3]}")
        print(f"Property Type: {p13115074[4]}")
        print(f"Listing Type: {p13115074[5]}")
        print(f"Price (USD): ${p13115074[6]:,.2f}")
        print(f"Area (sqm): {p13115074[7]:,.0f} m²")
        print(f"Price/sqm: ${p13115074[8]:,.2f}/m²")
    else:
        print("Post 13115074 not found in DB!")

    conn.close()
    print("\nMigration completed successfully!")

if __name__ == "__main__":
    run_migration()
