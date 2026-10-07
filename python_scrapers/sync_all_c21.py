"""
Century 21 Cambodia (C21) Full Catalog Synchronizer.
Downloads live listings from C21's internal REST API directly via HTTP
and saves them directly into properties.db with full GPS coordinates and metadata.
"""

import sys
import time
from datetime import datetime
import httpx

from scrapers.cambodia_re_scraper import (
    CambodiaReScraper,
    CATEGORY_TAXONOMY_MAP,
    HEADERS
)
from database import upsert_properties_batch, get_connection

PER_PAGE = 50  # Optimal batch size (sub-second query response with _embed)

def sync_c21(category: str = "All", max_pages: int = 180, delay: float = 0.3):
    """
    Fetch and save C21 listings in batches of 50 directly via HTTP REST API.
    Safely upserts after every single page so no progress is ever lost.
    """
    scraper = CambodiaReScraper()
    cat_id = CATEGORY_TAXONOMY_MAP.get(category)
    
    print("=" * 65)
    print("  CENTURY 21 CAMBODIA - DIRECT REST API CATALOG SYNC")
    print(f"  Category: {category} | Batch Size: {PER_PAGE} per page")
    print("=" * 65)

    with httpx.Client(headers=HEADERS, timeout=35.0, follow_redirects=True) as client:
        # 1. Probe total count header
        probe_url = f"https://cambodia-real-estate.com/wp-json/wp/v2/properties?per_page=1&page=1"
        if cat_id:
            probe_url += f"&property_type={cat_id}"
            
        try:
            resp = client.get(probe_url)
            total_items = int(resp.headers.get("x-wp-total", 8750))
            total_pages = int(resp.headers.get("x-wp-totalpages", (total_items // PER_PAGE) + 1))
        except Exception as e:
            print(f"[!] Probe error ({e}), using default catalog limits.")
            total_items = 8750
            total_pages = 175

        pages_to_fetch = min(max_pages, total_pages)
        print(f"[*] Total listings on C21: {total_items:,}")
        print(f"[*] Total pages to fetch: {pages_to_fetch} (up to {pages_to_fetch * PER_PAGE:,} listings)\n")

        total_saved = 0
        t_start = time.time()

        for page_num in range(1, pages_to_fetch + 1):
            p_t0 = time.time()
            url = f"https://cambodia-real-estate.com/wp-json/wp/v2/properties?per_page={PER_PAGE}&page={page_num}&_embed"
            if cat_id:
                url += f"&property_type={cat_id}"

            try:
                resp = client.get(url)

                if resp.status_code in [400, 404]:
                    print(f"[*] Page {page_num}: End of catalog reached.")
                    break

                if resp.status_code != 200:
                    print(f"[-] Page {page_num}: Status {resp.status_code}. Stopping.")
                    break

                posts = resp.json()
                if not posts or not isinstance(posts, list):
                    print(f"[*] Page {page_num}: No more items.")
                    break

                # Parse posts
                items = []
                for p_raw in posts:
                    parsed = scraper._parse_post(p_raw, default_category=category)
                    if parsed:
                        items.append(parsed.to_dict())

                # Upsert immediately
                saved = upsert_properties_batch(items)
                total_saved += saved
                p_elapsed = time.time() - p_t0
                pct = (total_saved / total_items) * 100 if total_items > 0 else 0

                print(
                    f"[{page_num:02d}/{pages_to_fetch}] "
                    f"Fetched {len(posts)} items -> Saved/Updated {saved} in {p_elapsed:.2f}s "
                    f"(Total: {total_saved:,}/{total_items:,} - {pct:.1f}%)"
                )

                time.sleep(delay)

            except Exception as e:
                print(f"[!] Error on page {page_num}: {e}")
                time.sleep(1)

        total_time = time.time() - t_start
        print("\n" + "=" * 65)
        print(f"  SYNC COMPLETE!")
        print(f"  Total Properties Saved/Updated: {total_saved:,}")
        print(f"  Total Duration: {total_time/60:.1f} minutes")
        print("=" * 65)

if __name__ == "__main__":
    import argparse
    parser = argparse.ArgumentParser(description="Sync all C21 properties via Direct REST API")
    parser.add_argument("--category", default="All", help="Category: All, Condo, House, Villa, Land, Commercial, Borey")
    parser.add_argument("--pages", type=int, default=180, help="Maximum pages to fetch (50 items/page)")
    args = parser.parse_args()

    sync_c21(category=args.category, max_pages=args.pages)
