"""
ARC (Asia Real Estate Cambodia - arc.com.kh) Scraper.
Uses direct REST API endpoint for ultra-fast and reliable listing ingestion,
supporting both map data scraping (full GPS coordinates) and filtered queries.
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

ARC_API_URL = "https://pms.arccambodia.com/v1/api/sale/website/property"
IMAGE_BASE_URL = "https://synassets.synpanel.com/7772cd23e82b258cf93c98e19340f571/"

CATEGORY_MAP = {
    "All": "",
    "Land": "1",
    "Condo": "2",
    "Villa": "3",
    "House": "3",
    "Commercial": "3"
}

HEADERS = {
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36",
    "Content-Type": "application/json",
    "Origin": "https://arc.com.kh",
    "Referer": "https://arc.com.kh/#/map",
}


class ArcScraper(BaseScraper):
    """Scrapes properties directly from ARC PMS API, including exact GPS coordinates."""

    def __init__(self):
        super().__init__("arc")

    def scrape(
        self,
        category: str = "All",
        max_pages: int = 2,
        progress_callback: Optional[Callable[[int, int, str], None]] = None,
        batch_callback: Optional[Callable[[List[PropertyItem]], int]] = None
    ) -> List[PropertyItem]:
        """
        Scrape properties from ARC.
        If max_pages >= 10, fetches the full map dataset in a single ultra-fast query.
        Otherwise fetches requested pages (60 items per page).
        """
        items: List[PropertyItem] = []
        cat_id = CATEGORY_MAP.get(category, "")

        # If user asks for high max_pages (e.g. >= 10), pull the full map dataset at once!
        if max_pages >= 10:
            return self.scrape_all_map(category=category, progress_callback=progress_callback, batch_callback=batch_callback)

        limit = 60
        with httpx.Client(headers=HEADERS, timeout=30.0) as client:
            for page in range(max_pages):
                offset = page * limit
                msg = f"ARC: Fetching page {page + 1}/{max_pages} (Offset: {offset})..."
                if progress_callback:
                    progress_callback(page + 1, max_pages, msg)

                payload = {
                    "company": "10",
                    "langId": 1,
                    "user": 0,
                    "projectName": "",
                    "propertyStatus": "",
                    "propertyCategory": cat_id,
                    "propertyType": "",
                    "totalBedroom": "",
                    "totalBathroom": "",
                    "priceStatus": "",  # Both Sale and Rent
                    "priceFrom": 0,
                    "priceTo": 0,
                    "orderBy": "",
                    "offset": offset,
                    "limit": limit,
                    "province": "",
                    "district": "",
                    "commune": "",
                    "landAreaStatus": "",
                    "landAreaFrom": 0,
                    "landAreaTo": 0,
                    "projectId": "",
                    "proRecomment": "",
                    "proMostView": "",
                    "fGarden": "",
                    "fClubHouse": "",
                    "fSecurityGuard": "",
                    "fElectricity": "",
                    "fGarbage": "",
                    "fAirCon": "",
                    "fGymSauna": "",
                    "fSuperMarket": "",
                    "fCCTV": "",
                    "fWaterSupply": "",
                    "fParking": "",
                    "fFireProtect": "",
                    "fPlayground": ""
                }

                try:
                    resp = client.post(ARC_API_URL, json=payload)
                    if resp.status_code != 200:
                        print(f"[ARC] HTTP error {resp.status_code}: {resp.text[:100]}")
                        break

                    data = resp.json()
                    prop_list = data.get("propertyList", [])
                    if not prop_list:
                        break

                    for p in prop_list:
                        item = self._parse_property(p)
                        if item:
                            items.append(item)

                except Exception as e:
                    print(f"[ARC] Error fetching page {page + 1}: {e}")
                    break

        return items

    def scrape_all_map(
        self,
        category: str = "All",
        progress_callback: Optional[Callable[[int, int, str], None]] = None,
        batch_callback: Optional[Callable[[List[PropertyItem]], int]] = None
    ) -> List[PropertyItem]:
        """
        Scrape all properties from ARC Map API in one shot (matches https://arc.com.kh/#/map).
        Retrieves all active listings with exact GPS coordinates.
        """
        cat_id = CATEGORY_MAP.get(category, "")
        if progress_callback:
            progress_callback(1, 2, "ARC Map: Requesting full nationwide map listings...")

        payload = {
            "company": "10",
            "langId": 1,
            "user": 0,
            "projectName": "",
            "propertyStatus": "",
            "propertyCategory": cat_id,
            "propertyType": "",
            "totalBedroom": "",
            "totalBathroom": "",
            "priceStatus": "",
            "priceFrom": 0,
            "priceTo": 0,
            "orderBy": "",
            "offset": 0,
            "limit": 1000000000,  # Exact parameter used by arc.com.kh/#/map frontend
            "province": "",
            "district": "",
            "commune": "",
            "landAreaStatus": "",
            "landAreaFrom": 0,
            "landAreaTo": 0,
            "projectId": "",
            "proRecomment": "",
            "proMostView": "",
            "fGarden": "",
            "fClubHouse": "",
            "fSecurityGuard": "",
            "fElectricity": "",
            "fGarbage": "",
            "fAirCon": "",
            "fGymSauna": "",
            "fSuperMarket": "",
            "fCCTV": "",
            "fWaterSupply": "",
            "fParking": "",
            "fFireProtect": "",
            "fPlayground": ""
        }

        items: List[PropertyItem] = []
        with httpx.Client(headers=HEADERS, timeout=60.0) as client:
            resp = client.post(ARC_API_URL, json=payload)
            if resp.status_code != 200:
                print(f"[ARC Map] Error: {resp.status_code}")
                return []

            data = resp.json()
            prop_list = data.get("propertyList", [])

            if progress_callback:
                progress_callback(2, 2, f"ARC Map: Ingesting {len(prop_list)} properties with GPS...")

            for p in prop_list:
                item = self._parse_property(p)
                if item:
                    items.append(item)

        if batch_callback and items:
            batch_callback(items)

        return items

    def _parse_property(self, p: dict) -> Optional[PropertyItem]:
        """Convert raw ARC PMS dictionary into PropertyItem dataclass."""
        try:
            prop_no = p.get("property_no") or str(p.get("pro_id", ""))
            title = p.get("property_name") or f"Property {prop_no}"

            # Purpose & Price
            purpose = p.get("listing_purpose")
            sale_price = clean_price(p.get("sale_price"))
            rent_price = clean_price(p.get("rent_price"))

            if purpose == 1:
                listing_type = "Rent"
                price = rent_price or sale_price
            elif purpose == 2:
                listing_type = "Sale"
                price = sale_price or rent_price
            else:
                if sale_price and sale_price > 0:
                    listing_type = "Sale"
                    price = sale_price
                elif rent_price and rent_price > 0:
                    listing_type = "Rent"
                    price = rent_price
                else:
                    listing_type = "Sale"
                    price = None

            # Area
            land_area = clean_area(p.get("ld_area"))
            build_area = clean_area(p.get("bd_up_area"))
            area = land_area if land_area else build_area

            # Price per sqm
            pp_sqm = None
            if price and area and area > 0:
                pp_sqm = round(price / area, 2)

            # GPS Coordinates
            lat = None
            lng = None
            try:
                raw_lat = p.get("map_lat")
                raw_lng = p.get("map_lng")
                if raw_lat and raw_lng:
                    lat_f = float(raw_lat)
                    lng_f = float(raw_lng)
                    if 8.5 <= lat_f <= 15.5 and 101.5 <= lng_f <= 108.5:
                        lat = round(lat_f, 6)
                        lng = round(lng_f, 6)
            except (ValueError, TypeError):
                lat, lng = None, None

            # Location Resolution
            address = p.get("property_address", "")
            loc = extract_location(f"{address} {title}")
            province = loc["province"] or "Phnom Penh"
            district = loc["district"]
            commune = loc.get("commune")

            # Property Type Normalization
            raw_cat = str(p.get("property_category", ""))
            if raw_cat == "1":
                ptype = "Land"
            elif raw_cat == "2":
                ptype = "Condo"
            else:
                ptype = normalize_property_type(title)

            # Detail URL
            prop_uuid = p.get("property_id", "")
            if prop_uuid:
                item_url = f"https://arc.com.kh/#/property-detail/{prop_uuid}"
            else:
                item_url = f"https://arc.com.kh/#/service/buy-sell-rent?no={prop_no}"

            # Image URL
            raw_img = p.get("property_thumbnail", "")
            if raw_img:
                if raw_img.startswith("http"):
                    img = raw_img
                else:
                    img = f"{IMAGE_BASE_URL}{raw_img}"
            else:
                img = ""

            # Beds & Baths
            bedrooms = None
            bathrooms = None
            try:
                if p.get("bd_bedroom") and str(p.get("bd_bedroom")).isdigit():
                    bedrooms = int(p.get("bd_bedroom"))
                if p.get("bd_bathroom") and str(p.get("bd_bathroom")).isdigit():
                    bathrooms = int(p.get("bd_bathroom"))
            except (ValueError, TypeError):
                pass

            return PropertyItem(
                source="arc",
                source_id=str(p.get("pro_id", "")),
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
                url=item_url,
                image_url=img,
                urgency_tag=extract_urgency_tag(f"{title} {address}"),
                latitude=lat,
                longitude=lng
            )
        except Exception as e:
            print(f"[ARC] Parse error: {e}")
            return None
