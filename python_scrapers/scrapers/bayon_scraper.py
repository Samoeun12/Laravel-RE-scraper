"""
Bayon App (bayonapp.com) Real Estate Scraper.
Extracts nationwide Cambodian listings directly via Bayon App REST API.
Includes high-resolution CDN images, agent details, and precise GPS coordinates.
"""

import httpx
from typing import List, Optional, Callable, Dict, Any
from scrapers.base_scraper import (
    BaseScraper,
    PropertyItem,
    normalize_property_type,
    extract_location,
    extract_urgency_tag
)

BAYON_API_URL = "https://agent.bayonapp.com/api/v1/property/fetch"
BAYON_TOKEN = "narongrealestate"

HEADERS = {
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36",
    "Accept": "application/json, text/plain, */*",
    "token": BAYON_TOKEN,
    "Origin": "https://bayonapp.com",
    "Referer": "https://bayonapp.com/",
}

TYPE_MAP = {
    "land": "Land",
    "farmland": "Land",
    "house": "House",
    "flat": "House",
    "villa": "Villa",
    "queenvilla": "Villa",
    "twinvilla": "Villa",
    "princevilla": "Villa",
    "linkvilla": "Villa",
    "condo": "Condo",
    "apartment": "Apartment",
    "commercial": "Commercial",
    "building": "Commercial",
    "shophouse": "Commercial",
    "office": "Commercial",
    "warehouse": "Warehouse",
}


class BayonScraper(BaseScraper):
    """Scrapes properties directly from the Bayon App mobile/web REST API."""

    def __init__(self):
        super().__init__("bayon")

    def scrape(
        self,
        category: str = "All",
        max_pages: int = 2,
        progress_callback: Optional[Callable[[int, int, str], None]] = None,
        batch_callback: Optional[Callable[[List[PropertyItem]], int]] = None
    ) -> List[PropertyItem]:
        items: List[PropertyItem] = []
        seen_ids = set()
        page_size = 50

        with httpx.Client(headers=HEADERS, timeout=25.0) as client:
            for page in range(1, max_pages + 1):
                skip = (page - 1) * page_size
                if progress_callback:
                    progress_callback(
                        page,
                        max_pages,
                        f"Bayon App: Fetching page {page}/{max_pages} (offset {skip})..."
                    )

                url = f"{BAYON_API_URL}?skip={skip}&limit={page_size}"
                try:
                    resp = client.get(url)
                    if resp.status_code != 200:
                        print(f"[BayonScraper] HTTP error {resp.status_code} at page {page}")
                        break

                    data = resp.json()
                    content = data.get("data", {}).get("content", [])
                    if not content:
                        print(f"[BayonScraper] No content returned at page {page}. End of feed.")
                        break

                    batch: List[PropertyItem] = []
                    for raw in content:
                        item = self._parse_item(raw)
                        if item and item.source_id not in seen_ids:
                            # Optional category filter
                            if category and category.lower() != "all":
                                if item.property_type.lower() != category.lower():
                                    continue

                            seen_ids.add(item.source_id)
                            batch.append(item)
                            items.append(item)

                    if batch_callback and batch:
                        batch_callback(batch)

                except Exception as e:
                    print(f"[BayonScraper] Error fetching page {page}: {e}")
                    break

        return items

    def _parse_item(self, raw: Dict[str, Any]) -> Optional[PropertyItem]:
        prop_id = str(raw.get("_id") or raw.get("code") or "")
        if not prop_id:
            return None

        code = str(raw.get("code") or "").strip()
        raw_type = str(raw.get("type") or "other").strip().lower()
        prop_type = TYPE_MAP.get(raw_type, normalize_property_type(raw_type))

        # Listing Type: Sale or Rent
        group_type = str(raw.get("groupType") or "sale").strip().lower()
        listing_type = "Rent" if "rent" in group_type else "Sale"

        # Pricing
        price_val = raw.get("price")
        last_price_val = raw.get("lastPrice")
        price = None
        if price_val is not None and float(price_val) > 0:
            price = float(price_val)
        elif last_price_val is not None and float(last_price_val) > 0:
            price = float(last_price_val)

        # Area
        area_val = raw.get("size")
        area = None
        if area_val:
            try:
                area = float(str(area_val).replace(",", "").strip())
            except (ValueError, TypeError):
                area = None

        # $/sqm price
        sqm_val = raw.get("pricePerSquare")
        price_per_sqm = None
        if sqm_val:
            try:
                price_per_sqm = round(float(str(sqm_val).replace(",", "").strip()), 2)
            except (ValueError, TypeError):
                price_per_sqm = None

        if not price_per_sqm and price and area and area > 0:
            price_per_sqm = round(price / area, 2)

        # Location extraction
        loc_doc = raw.get("locationDoc") or {}
        dist_doc = raw.get("districtDoc") or {}

        province = loc_doc.get("name") or "Phnom Penh"
        kh_province = loc_doc.get("khName")
        district = dist_doc.get("name") or ""
        kh_district = dist_doc.get("khName")

        address_str = str(raw.get("address") or "").strip()
        if address_str in ["-", "", "none"]:
            addr_parts = [p for p in [district, province] if p]
            address_str = ", ".join(addr_parts) if addr_parts else "Cambodia"

        # Title resolution: extract best headline from desc if title is generic
        raw_title = str(raw.get("title") or "").strip()
        desc = str(raw.get("desc") or "").strip()

        title = ""
        if raw_title and raw_title not in ["-", ""] and not raw_title.startswith("Bayon B"):
            title = raw_title
        elif desc:
            lines = [l.strip() for l in desc.split("\n") if l.strip()]
            for l in lines:
                cleaned_line = l.lstrip("📍🏝🏠⚡️👉🔹🔸🔻 ").strip()
                if len(cleaned_line) >= 5:
                    title = cleaned_line[:120]
                    break

        if not title:
            title = f"{prop_type} for {listing_type}"
            if district or province:
                title += f" in {district or province}"
            if code:
                title += f" (#{code})"
        elif code and f"#{code}" not in title and code not in title:
            title = f"{title} (#{code})"

        # Photos
        url_list = raw.get("urlList") or []
        image_url = url_list[0] if url_list else "https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=800&q=80"

        # Precise GPS Coordinates
        pos = raw.get("position") or {}
        lat = pos.get("lat")
        lng = pos.get("lng")
        if lat is not None:
            lat = float(lat)
        if lng is not None:
            lng = float(lng)

        # Beds / Baths
        beds = raw.get("numBed")
        baths = raw.get("numBathroom")
        bedrooms = int(beds) if beds is not None and int(beds) > 0 else None
        bathrooms = int(baths) if baths is not None and int(baths) > 0 else None

        # Urgency tag
        urgency = extract_urgency_tag(f"{title} {desc}")

        # Web Canonical URL
        web_url = f"https://bayonapp.com/#/properties/{prop_id}"

        return PropertyItem(
            source="bayon",
            title=title,
            source_id=prop_id,
            property_type=prop_type,
            listing_type=listing_type,
            price_usd=price,
            area_sqm=area,
            price_per_sqm=price_per_sqm,
            province=province,
            district=district,
            commune=None,
            address=address_str,
            bedrooms=bedrooms,
            bathrooms=bathrooms,
            url=web_url,
            image_url=image_url,
            urgency_tag=urgency,
            latitude=lat,
            longitude=lng
        )
