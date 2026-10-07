"""
Realestate.com.kh Intelligent High-Speed Scraper.
Queries Realestate.com.kh's internal portal JSON REST endpoint directly,
with automated fallback to Next.js __NEXT_DATA__ SSR cache.

Benefits:
1. High Speed: Retrieves 50-100 structured property listings in ~1-2 seconds.
2. Complete Data: Direct access to 41,500+ live property listings across Cambodia.
3. Native GPS: Extracts exact latitude and longitude coordinates.
4. Accurate Pricing: Distinguishes Sale vs Rent, resolves $/m² unit prices.
5. Rich Metadata: Extracts exact land area, floor area, bedrooms, bathrooms, and urgency tags.
"""

import re
import json
import httpx
from typing import List, Optional, Callable, Dict, Any

from scrapers.base_scraper import (
    BaseScraper,
    PropertyItem,
    clean_price,
    clean_area,
    normalize_property_type,
    extract_location,
    extract_urgency_tag,
    normalize_listing_type,
    normalize_pricing_and_unit
)

BASE_URL = "https://www.realestate.com.kh"
API_ENDPOINT = "https://www.realestate.com.kh/api/portal/pages/results/"

CATEGORY_PATH_MAP = {
    "All": "/buy/",
    "Land": "/buy/land/",
    "Condo": "/buy/condo/",
    "Villa": "/buy/villa/",
    "House": "/buy/house/",
    "Apartment": "/buy/apartment/",
    "Commercial": "/buy/commercial/",
    "Rent": "/rent/"
}

HEADERS = {
    "User-Agent": (
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
        "(KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36"
    ),
    "Accept": "application/json, text/plain, */*",
    "Accept-Language": "en-US,en;q=0.9",
    "Referer": "https://www.realestate.com.kh/buy/"
}


class RealestateScraper(BaseScraper):
    """Scrapes listings from realestate.com.kh using its JSON API and Next.js data."""

    def __init__(self):
        super().__init__("realestate")

    def scrape(
        self,
        category: str = "All",
        max_pages: int = 2,
        progress_callback: Optional[Callable[[int, int, str], None]] = None,
        batch_callback: Optional[Callable[[List[PropertyItem]], int]] = None
    ) -> List[PropertyItem]:
        items: List[PropertyItem] = []
        path = CATEGORY_PATH_MAP.get(category, "/buy/")
        default_ltype = "Rent" if "/rent/" in path else "Sale"

        with httpx.Client(headers=HEADERS, timeout=20.0, follow_redirects=True) as client:
            for page in range(1, max_pages + 1):
                msg = f"Realestate.com.kh: Fetching page {page}/{max_pages} ({category})..."
                if progress_callback:
                    progress_callback(page, max_pages, msg)

                page_items = self._fetch_page(client, path, page, default_ltype)
                if not page_items:
                    # If direct API returned no items, attempt fallback via Next.js SSR
                    page_items = self._fetch_page_ssr_fallback(client, path, page, default_ltype)

                if not page_items:
                    break

                items.extend(page_items)
                if batch_callback and page_items:
                    batch_callback(page_items)

        return items

    def _fetch_page(
        self,
        client: httpx.Client,
        path: str,
        page: int,
        default_ltype: str
    ) -> List[PropertyItem]:
        """Query internal REST endpoint directly."""
        params = {
            "pathname": path,
            "page": page,
            "page_size": 50,
            "search_languages": "en,km,zh-hans"
        }
        try:
            resp = client.get(API_ENDPOINT, params=params)
            if resp.status_code != 200:
                return []

            data = resp.json()
            raw_results = data.get("results", [])
            items = []
            for raw_it in raw_results:
                parsed = self._parse_item(raw_it, default_ltype)
                if parsed:
                    items.append(parsed)
            return items
        except Exception as e:
            print(f"[Realestate] API fetch error on page {page}: {e}")
            return []

    def _fetch_page_ssr_fallback(
        self,
        client: httpx.Client,
        path: str,
        page: int,
        default_ltype: str
    ) -> List[PropertyItem]:
        """Fallback: Parse Next.js __NEXT_DATA__ JSON embedded in HTML."""
        url = f"{BASE_URL}{path}?page={page}" if page > 1 else f"{BASE_URL}{path}"
        try:
            html_headers = dict(HEADERS)
            html_headers["Accept"] = "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8"
            resp = client.get(url, headers=html_headers)
            if resp.status_code != 200:
                return []

            match = re.search(r'<script id="__NEXT_DATA__" type="application/json">(.*?)</script>', resp.text)
            if not match:
                return []

            next_data = json.loads(match.group(1))
            cache_data = next_data.get("props", {}).get("pageProps", {}).get("cacheData", {})
            results_obj = cache_data.get("results", {})
            r_data = results_obj.get("data", {}) if isinstance(results_obj, dict) else {}
            raw_results = r_data.get("results", [])

            items = []
            for raw_it in raw_results:
                parsed = self._parse_item(raw_it, default_ltype)
                if parsed:
                    items.append(parsed)
            return items
        except Exception as e:
            print(f"[Realestate] SSR fallback error on page {page}: {e}")
            return []

    def _parse_item(self, it: Dict[str, Any], default_ltype: str = "Sale") -> Optional[PropertyItem]:
        """Parse raw Realestate.com.kh item into PropertyItem."""
        pid = str(it.get("id", ""))
        headline = it.get("headline") or it.get("title_img_alt") or "Realestate.com.kh Property"
        title = headline.strip()
        if not title or len(title) < 3:
            return None

        # Determine Listing Type (Sale vs Rent)
        raw_ltype = str(it.get("listing_type", "")).lower()
        disp_rent = str(it.get("display_rent", "")).strip()
        disp_price = str(it.get("display_price", "")).strip()

        if "rent" in raw_ltype or (disp_rent and disp_rent != "POA" and (not disp_price or disp_price == "POA")):
            listing_type = "Rent"
        elif "sale" in raw_ltype:
            listing_type = "Sale"
        else:
            listing_type = default_ltype

        listing_type = normalize_listing_type(title, "", listing_type)

        # Price extraction
        price_str = disp_rent if listing_type == "Rent" else disp_price
        price = None
        if price_str and price_str != "POA":
            price = clean_price(price_str)

        # Category / Property Type
        cat_name = it.get("category_name") or ""
        ptype = normalize_property_type(f"{cat_name} {title}")

        # Specifications (Area, Bedrooms, Bathrooms)
        area = None
        bedrooms = None
        bathrooms = None

        specs = it.get("specifications", {})
        detail_specs = specs.get("detail", []) if isinstance(specs, dict) else []

        land_area = None
        floor_area = None

        for spec in detail_specs:
            stype = spec.get("type")
            slabel = spec.get("label", "")
            if stype == "bedrooms":
                m = re.search(r"(\d+)", slabel)
                if m:
                    bedrooms = int(m.group(1))
            elif stype == "bathrooms":
                m = re.search(r"(\d+)", slabel)
                if m:
                    bathrooms = int(m.group(1))
            elif stype == "land_area":
                land_area = clean_area(slabel)
            elif stype == "floor_area":
                floor_area = clean_area(slabel)

        if ptype == "Land":
            area = land_area or floor_area
        else:
            area = floor_area or land_area

        if not area:
            area = clean_area(title)

        # Structured Location
        raw_addr = str(it.get("address") or "")
        loc = extract_location(raw_addr)
        province = loc["province"] or "Phnom Penh"
        district = loc["district"]
        commune = loc.get("commune", "")
        address = raw_addr.strip(" ,") or (f"{district}, {province}" if district else province)

        # Native Geocoordinates (exact GPS)
        lat = None
        lon = None
        try:
            raw_lat = it.get("address_latitude")
            raw_lon = it.get("address_longitude")
            if raw_lat and raw_lon:
                f_lat = float(raw_lat)
                f_lon = float(raw_lon)
                if 8.0 <= f_lat <= 16.0 and 100.0 <= f_lon <= 110.0:
                    lat = round(f_lat, 6)
                    lon = round(f_lon, 6)
        except (ValueError, TypeError):
            pass

        # Urgency Tag & Ribbons
        urgency_tag = it.get("ribbon") or ""
        if not urgency_tag:
            labels = it.get("labels", [])
            if labels and isinstance(labels, list) and labels:
                urgency_tag = labels[0].get("label", "")
        if not urgency_tag:
            urgency_tag = extract_urgency_tag(title)

        # URLs and Media
        rel_url = it.get("url") or f"/buy/{pid}/"
        url = f"https://www.realestate.com.kh{rel_url}" if rel_url.startswith("/") else rel_url

        images = it.get("images", [])
        image_url = ""
        if images and isinstance(images, list) and images:
            image_url = images[0].get("url", "")
            if not image_url and images[0].get("thumbnails"):
                image_url = images[0]["thumbnails"][-1].get("url", "")

        # Pricing & unit rate normalization
        price, pp_sqm, listing_type = normalize_pricing_and_unit(
            title=title,
            desc=address,
            price=price,
            area=area,
            property_type=ptype,
            listing_type=listing_type
        )

        return PropertyItem(
            source="realestate",
            source_id=pid,
            title=title,
            property_type=ptype,
            listing_type=listing_type,
            price_usd=price,
            area_sqm=area,
            price_per_sqm=pp_sqm,
            province=province,
            district=district,
            commune=commune,
            address=address,
            bedrooms=bedrooms,
            bathrooms=bathrooms,
            url=url,
            image_url=image_url,
            urgency_tag=urgency_tag,
            latitude=lat,
            longitude=lon
        )
