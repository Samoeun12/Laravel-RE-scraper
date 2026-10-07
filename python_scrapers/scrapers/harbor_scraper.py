"""
Harbor Property (harbor-property.com) Scraper.
Extracts listings directly through Harbor Property's REST API endpoints.
"""

import httpx
from typing import List, Optional, Callable
from scrapers.base_scraper import (
    BaseScraper,
    PropertyItem,
    clean_price,
    clean_area,
    normalize_property_type,
    extract_location,
    extract_urgency_tag
)

HARBOR_LIST_URL = "https://www.harbor-property.com/api/Home/GetHouseList"
HARBOR_OTMZT_URL = "https://www.harbor-property.com/api/House/GetOtmztList?rsType=2"

BUILDING_TYPE_MAP = {
    1: "Condo",
    2: "Villa",
    3: "House",
    6: "Warehouse",
    8: "Land",
    9: "Villa",
    10: "Shophouse",
    11: "Apartment"
}

HEADERS = {
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
    "Accept": "application/json, text/plain, */*",
    "Content-Type": "application/json",
    "Origin": "https://www.harbor-property.com",
    "Referer": "https://www.harbor-property.com/en/house/buy",
}


class HarborScraper(BaseScraper):
    """Scrapes properties from Harbor-Property JSON API."""

    def __init__(self):
        super().__init__("harbor")

    def scrape(
        self,
        category: str = "All",
        max_pages: int = 2,
        progress_callback: Optional[Callable[[int, int, str], None]] = None,
        batch_callback: Optional[Callable[[List[PropertyItem]], int]] = None
    ) -> List[PropertyItem]:
        items: List[PropertyItem] = []
        seen_ids = set()

        with httpx.Client(headers=HEADERS, timeout=20.0) as client:
            # 1. Fetch GetHouseList
            if progress_callback:
                progress_callback(1, max_pages, "Harbor: Fetching curated house listings...")

            try:
                resp = client.post(HARBOR_LIST_URL, json={})
                if resp.status_code == 200:
                    data = resp.json()
                    sections = data.get("body", [])
                    for sec in sections:
                        hl = sec.get("HouseList", [])
                        for h in hl:
                            item = self._parse_harbor_house(h)
                            if item and item.source_id not in seen_ids:
                                seen_ids.add(item.source_id)
                                items.append(item)
            except Exception as e:
                print(f"[Harbor] Error fetching GetHouseList: {e}")

            # 2. Fetch GetOtmztList (RS Type 2 = Sale)
            if progress_callback:
                progress_callback(2, max_pages, "Harbor: Fetching optimized sale listings...")

            try:
                resp2 = client.get(HARBOR_OTMZT_URL)
                if resp2.status_code == 200:
                    data2 = resp2.json()
                    sections2 = data2.get("body", [])
                    for sec in sections2:
                        hl = sec.get("HouseList", [])
                        for h in hl:
                            item = self._parse_harbor_house(h)
                            if item and item.source_id not in seen_ids:
                                seen_ids.add(item.source_id)
                                items.append(item)
            except Exception as e:
                print(f"[Harbor] Error fetching GetOtmztList: {e}")

        # Filter by category if requested
        if category and category != "All":
            items = [it for it in items if it.property_type.lower() == category.lower()]

        if batch_callback and items:
            batch_callback(items)

        return items

    def _parse_harbor_house(self, h: dict) -> Optional[PropertyItem]:
        house_id = h.get("HouseId")
        if not house_id:
            return None

        title = h.get("Title") or f"Harbor Property #{house_id}"
        price = clean_price(h.get("Price") or h.get("Total"))
        area = clean_area(h.get("Area"))
        
        # RsType: 1 = Rent, 2 = Sale
        rs_type = "Sale" if h.get("RsType") == 2 else "Rent"

        b_type_id = h.get("BuildingTypeId")
        ptype = BUILDING_TYPE_MAP.get(b_type_id, normalize_property_type(title))

        pp_sqm = None
        if price and area and area > 0:
            pp_sqm = round(price / area, 2)

        suffix = h.get("UrlSuffix", "")
        url = f"https://www.harbor-property.com/en/house/detail/{house_id}/{suffix}"

        loc = extract_location(title)
        province = loc["province"] or "Phnom Penh"
        district = loc["district"]

        return PropertyItem(
            source="harbor",
            source_id=str(house_id),
            title=title,
            property_type=ptype,
            listing_type=rs_type,
            price_usd=price,
            area_sqm=area,
            price_per_sqm=pp_sqm,
            province=province,
            district=district,
            bedrooms=h.get("BedRoom"),
            bathrooms=h.get("BathRoom"),
            url=url,
            image_url=h.get("ImgUrl", ""),
            urgency_tag=extract_urgency_tag(title)
        )
