"""
Base scraper definition and text/data cleaning utilities for Cambodia real estate.
"""

import re
import httpx
from abc import ABC, abstractmethod
from typing import Dict, Any, Optional, List, Callable, Tuple
from dataclasses import dataclass, asdict
from datetime import datetime


def normalize_listing_type(title: str, desc: str = "", current_type: str = "Sale") -> str:
    """
    Detect whether listing is Sale or Rent based on Khmer, English, and Chinese keywords.
    Properly handles edge cases like 'ដីលក់(មានចំណូលជួល)' (Sale with rental income).
    """
    full = f"{title} {desc}".lower()
    full_raw = f"{title} {desc}"

    # Explicit rental keywords
    rent_khmer = any(k in full_raw for k in [
        'សម្រាប់ជួល', 'ដាក់ជួល', 'ផ្ទះជួល', 'ដីជួល', 'បន្ទប់ជួល', 
        'ឃ្លាំងជួល', 'អគារជួល', 'ខុនដូជួល', 'វីឡាជួល', 'ជួលបន្ទាន់',
        'ផ្ទះសម្រាប់ជួល', 'ដីសម្រាប់ជួល', 'បន្ទប់សម្រាប់ជួល', 'ឃ្លាំងសម្រាប់ជួល'
    ])
    rent_en = any(k in full for k in [
        'for rent', 'to rent', 'for lease', 'to lease', 'rental fee', 
        'warehouse for rent', 'house for rent', 'condo for rent', 'apartment for rent',
        'flat for rent', 'villa for rent', 'office for rent', 'room for rent'
    ])
    rent_cn = any(k in full_raw for k in ['出租', '招租', '租金', '求租'])

    has_chhuol = 'ជួល' in full_raw
    has_loak = 'លក់' in full_raw or 'sale' in full or '出售' in full_raw

    # Tenanted / investment sale with rental income: e.g. "ដីលក់(មានចំណូលជួល)"
    has_rental_income = (
        any(k in full_raw for k in ['ចំណូលជួល', 'មានចំណូលស្រាប់', '带租约']) or
        any(k in full for k in ['rental income', 'with income', 'tenant', 'tenanted'])
    )
    if has_loak and has_rental_income:
        return "Sale"

    # Dual options: "លក់ ឬ ជួល" / "sale or rent" -> Keep as Sale
    if has_loak and ('ឬជួល' in full_raw or 'ឬ ជួល' in full_raw or 'or rent' in full or '或出租' in full_raw):
        return "Sale"

    if rent_khmer or rent_en or rent_cn:
        if not has_loak:
            return "Rent"

    if has_chhuol and not has_loak:
        return "Rent"

    return current_type or "Sale"


def normalize_pricing_and_unit(
    title: str,
    desc: str = "",
    price: Optional[float] = None,
    area: Optional[float] = None,
    property_type: str = "Other",
    listing_type: str = "Sale"
) -> Tuple[Optional[float], Optional[float], str]:
    """
    Detects if the quoted price is actually a unit price ($/m²) rather than total price ($).
    Handles Cambodian market conventions where sellers enter $/m² into portal price fields
    (e.g., $550/m² for a 20,758 m² parcel entered as $550).
    
    Returns:
        (total_price_usd, price_per_sqm, listing_type)
    """
    norm_listing_type = normalize_listing_type(title, desc, listing_type)
    
    if not price or not area or area <= 0:
        return price, None, norm_listing_type
        
    # For rentals, price is the monthly rental rate ($/mo)
    # price_per_sqm is the rental rate per sqm ($/m²/mo)
    if norm_listing_type == "Rent":
        return price, round(price / area, 2), "Rent"
        
    # If dummy price (e.g. $1, $5 contact for price placeholder), do not inflate
    if price < 5.0:
        return price, round(price / area, 2), norm_listing_type

    full_text = f"{title} {desc}".lower()
    full_text_raw = f"{title} {desc}"

    # Check explicit $/m2 mentions in text
    has_explicit_per_sqm = bool(re.search(
        r'(?:\$|usd)?\s*\d+(?:[.,]\d+)?\s*(?:\$|usd)?\s*(?:\/|\s*ក្នុង\s*១?\s*)(?:m²|m2|sqm|sq\.m|ម៉ែត្រការ៉េ)',
        full_text
    ))
    if not has_explicit_per_sqm:
        has_explicit_per_sqm = any(k in full_text for k in [
            '/m²', '/m2', '$/m²', '$/m2', '/sqm', '$/sqm', 
            'ក្នុង១ម៉ែត្រ', 'ក្នុង 1 ម៉ែត្រ', '1m2', '1m²', 'ក្នុងមួយម៉ែត្រ', 'ក្នុង១m'
        ])

    is_land_or_commercial = (
        property_type in ['Land', 'Commercial', 'Warehouse', 'Other'] or 
        any(k in full_text_raw for k in ['ដី', 'land', 'រោងចក្រ', 'factory', 'ឃ្លាំង', 'warehouse'])
    )

    # Condition 1: Explicit $/m² in text and price is within realistic per-sqm range ($5 to $10,000/m²)
    if has_explicit_per_sqm and 5.0 <= price <= 10000.0:
        pp_sqm = price
        total_price = round(pp_sqm * area, 2)
        return total_price, pp_sqm, "Sale"

    # Condition 2: Ratio heuristic for Land/Commercial/Warehouse
    # In Cambodia, no land parcel >= 80 m² is sold for < $15/m² total purchase price with a nominal price <= $8,000.
    # (e.g., $550 for 20,758 m² is $0.026/m² -> clearly $550/m²)
    if is_land_or_commercial and area >= 80 and 10.0 <= price <= 8000.0:
        ratio = price / area
        if ratio < 15.0:
            pp_sqm = price
            total_price = round(pp_sqm * area, 2)
            return total_price, pp_sqm, "Sale"

    # Standard total purchase price
    return price, round(price / area, 2), "Sale"


@dataclass
class PropertyItem:
    source: str
    title: str
    source_id: Optional[str] = ""
    property_type: Optional[str] = "Other"
    listing_type: Optional[str] = "Sale"
    price_usd: Optional[float] = None
    area_sqm: Optional[float] = None
    price_per_sqm: Optional[float] = None
    province: Optional[str] = ""
    district: Optional[str] = ""
    commune: Optional[str] = ""
    address: Optional[str] = ""
    bedrooms: Optional[int] = None
    bathrooms: Optional[int] = None
    url: Optional[str] = ""
    image_url: Optional[str] = ""
    urgency_tag: Optional[str] = ""
    latitude: Optional[float] = None
    longitude: Optional[float] = None
    scraped_at: Optional[str] = None

    def to_dict(self) -> Dict[str, Any]:
        d = asdict(self)
        if not d.get("scraped_at"):
            d["scraped_at"] = datetime.utcnow().isoformat()
        
        # Ensure pricing, unit rates, and listing types are normalized
        price, pp_sqm, ltype = normalize_pricing_and_unit(
            title=d.get("title", ""),
            desc=d.get("address", ""),
            price=d.get("price_usd"),
            area=d.get("area_sqm"),
            property_type=d.get("property_type", "Other"),
            listing_type=d.get("listing_type", "Sale")
        )
        d["price_usd"] = price
        d["price_per_sqm"] = pp_sqm
        d["listing_type"] = ltype
        return d


class BaseScraper(ABC):
    """Abstract base class for all real estate platform scrapers."""

    def __init__(self, name: str):
        self.name = name

    @abstractmethod
    def scrape(
        self,
        category: str = "All",
        max_pages: int = 1,
        progress_callback: Optional[Callable[[int, int, str], None]] = None,
        batch_callback: Optional[Callable[[List[PropertyItem]], int]] = None
    ) -> List[PropertyItem]:
        """
        Scrape property listings.
        :param category: Target category ('All', 'Land', 'Condo', etc.)
        :param max_pages: Number of pages to retrieve
        :param progress_callback: Optional fn(current_page, max_pages, status_msg)
        :param batch_callback: Optional fn(batch_items) called per page to stream saves into DB
        :return: List of PropertyItem instances
        """
        pass


# Normalization Utilities

PROVINCES_MAP = {
    # All 25 Provinces of Cambodia (English & Khmer & common variants)
    "phnom penh": "Phnom Penh",
    "ភ្នំពេញ": "Phnom Penh",
    "siem reap": "Siem Reap",
    "សៀមរាប": "Siem Reap",
    "preah sihanouk": "Preah Sihanouk",
    "sihanoukville": "Preah Sihanouk",
    "sihanouk": "Preah Sihanouk",
    "kampong saom": "Preah Sihanouk",
    "kompong som": "Preah Sihanouk",
    "ព្រះសីហនុ": "Preah Sihanouk",
    "kandal": "Kandal",
    "កណ្តាល": "Kandal",
    "កណ្ដាល": "Kandal",
    "kampot": "Kampot",
    "កំពត": "Kampot",
    "battambang": "Battambang",
    "បាត់ដំបង": "Battambang",
    "kep": "Kep",
    "កែប": "Kep",
    "koh kong": "Koh Kong",
    "កោះកុង": "Koh Kong",
    "kampong speu": "Kampong Speu",
    "kompong speu": "Kampong Speu",
    "កំពង់ស្ពឺ": "Kampong Speu",
    "kampong cham": "Kampong Cham",
    "kompong cham": "Kampong Cham",
    "កំពង់ចាម": "Kampong Cham",
    "takeo": "Takeo",
    "តាកែវ": "Takeo",
    "kampong chhnang": "Kampong Chhnang",
    "kompong chhnang": "Kampong Chhnang",
    "កំពង់ឆ្នាំង": "Kampong Chhnang",
    "kampong thom": "Kampong Thom",
    "kompong thom": "Kampong Thom",
    "កំពង់ធំ": "Kampong Thom",
    "prey veng": "Prey Veng",
    "ព្រៃវែង": "Prey Veng",
    "svay rieng": "Svay Rieng",
    "ស្វាយរៀង": "Svay Rieng",
    "pursat": "Pursat",
    "ពោធិ៍សាត់": "Pursat",
    "banteay meanchey": "Banteay Meanchey",
    "បន្ទាយមានជ័យ": "Banteay Meanchey",
    "pailin": "Pailin",
    "ប៉ៃលិន": "Pailin",
    "kratie": "Kratie",
    "ក្រចេះ": "Kratie",
    "stung treng": "Stung Treng",
    "ស្ទឹងត្រែង": "Stung Treng",
    "ratanakiri": "Ratanakiri",
    "ratanak kiri": "Ratanakiri",
    "រតនគិរី": "Ratanakiri",
    "mondulkiri": "Mondulkiri",
    "mondul kiri": "Mondulkiri",
    "មណ្ឌលគិរី": "Mondulkiri",
    "preah vihear": "Preah Vihear",
    "ព្រះវិហារ": "Preah Vihear",
    "oddar meanchey": "Oddar Meanchey",
    "otdar meanchey": "Oddar Meanchey",
    "ឧត្តរមានជ័យ": "Oddar Meanchey",
    "tboung khmum": "Tboung Khmum",
    "tbong khmum": "Tboung Khmum",
    "ត្បូងឃ្មុំ": "Tboung Khmum",
}

# Mapping of district/commune keywords to (Standard District Name, Associated Province)
DISTRICTS_MAP = {
    # Phnom Penh Khans
    "chamkarmon": ("Chamkarmon", "Phnom Penh"),
    "chamkar mon": ("Chamkarmon", "Phnom Penh"),
    "ចំការមន": ("Chamkarmon", "Phnom Penh"),
    "bkk": ("Boeng Keng Kang", "Phnom Penh"),
    "boeung keng kang": ("Boeng Keng Kang", "Phnom Penh"),
    "boeng keng kang": ("Boeng Keng Kang", "Phnom Penh"),
    "បឹងកេងកង": ("Boeng Keng Kang", "Phnom Penh"),
    "daun penh": ("Daun Penh", "Phnom Penh"),
    "doun penh": ("Daun Penh", "Phnom Penh"),
    "ដូនពេញ": ("Daun Penh", "Phnom Penh"),
    "toul kork": ("Toul Kork", "Phnom Penh"),
    "tuol kouk": ("Toul Kork", "Phnom Penh"),
    "ទួលគោក": ("Toul Kork", "Phnom Penh"),
    "sen sok": ("Sen Sok", "Phnom Penh"),
    "saensokh": ("Sen Sok", "Phnom Penh"),
    "sensok": ("Sen Sok", "Phnom Penh"),
    "សែនសុខ": ("Sen Sok", "Phnom Penh"),
    "chroy changvar": ("Chroy Changvar", "Phnom Penh"),
    "chrouy changvar": ("Chroy Changvar", "Phnom Penh"),
    "ជ្រោយចង្វារ": ("Chroy Changvar", "Phnom Penh"),
    "khan mean chey": ("Mean Chey", "Phnom Penh"),
    "khan meanchey": ("Mean Chey", "Phnom Penh"),
    "ខណ្ឌមានជ័យ": ("Mean Chey", "Phnom Penh"),
    "chbar ampov": ("Chbar Ampov", "Phnom Penh"),
    "ច្បារអំពៅ": ("Chbar Ampov", "Phnom Penh"),
    "russey keo": ("Russey Keo", "Phnom Penh"),
    "ruessei kaev": ("Russey Keo", "Phnom Penh"),
    "ឫស្សីកែវ": ("Russey Keo", "Phnom Penh"),
    "ឬស្សីកែវ": ("Russey Keo", "Phnom Penh"),
    "por sen chey": ("Por Sen Chey", "Phnom Penh"),
    "porsen chey": ("Por Sen Chey", "Phnom Penh"),
    "por senchey": ("Por Sen Chey", "Phnom Penh"),
    "ពោធិ៍សែនជ័យ": ("Por Sen Chey", "Phnom Penh"),
    "ពោធិសែនជ័យ": ("Por Sen Chey", "Phnom Penh"),
    "dangkao": ("Dangkao", "Phnom Penh"),
    "dang kor": ("Dangkao", "Phnom Penh"),
    "ដង្កោ": ("Dangkao", "Phnom Penh"),
    "prek pnov": ("Prek Pnov", "Phnom Penh"),
    "praek pnov": ("Prek Pnov", "Phnom Penh"),
    "ព្រែកព្នៅ": ("Prek Pnov", "Phnom Penh"),
    "kamboul": ("Kamboul", "Phnom Penh"),
    "kambol": ("Kamboul", "Phnom Penh"),
    "កំបូល": ("Kamboul", "Phnom Penh"),

    # Mondulkiri
    "saen monourom": ("Krong Saen Monourom", "Mondulkiri"),
    "sen monorom": ("Krong Saen Monourom", "Mondulkiri"),
    "senmonorom": ("Krong Saen Monourom", "Mondulkiri"),
    "krong saen monourom": ("Krong Saen Monourom", "Mondulkiri"),
    "សែនមនោរម្យ": ("Krong Saen Monourom", "Mondulkiri"),
    "spean mean chey": ("Krong Saen Monourom", "Mondulkiri"),
    "ស្ពានមានជ័យ": ("Krong Saen Monourom", "Mondulkiri"),
    "ou reang": ("Ou Reang", "Mondulkiri"),
    "pechr chenda": ("Pechr Chenda", "Mondulkiri"),
    "koh nheaek": ("Koh Nheaek", "Mondulkiri"),

    # Kandal
    "ta khmau": ("Ta Khmau", "Kandal"),
    "តាខ្មៅ": ("Ta Khmau", "Kandal"),
    "angk snuol": ("Angk Snuol", "Kandal"),
    "អង្គស្នួល": ("Angk Snuol", "Kandal"),
    "kien svay": ("Kien Svay", "Kandal"),
    "kiensvay": ("Kien Svay", "Kandal"),
    "khsach kandal": ("Khsach Kandal", "Kandal"),
    "mukh kampul": ("Mukh Kampul", "Kandal"),
    "ponhea lueu": ("Ponhea Lueu", "Kandal"),
    "sa ang": ("Sa'ang", "Kandal"),

    # Kampot
    "chhuk": ("Chhuk", "Kampot"),
    "ឈូក": ("Chhuk", "Kampot"),
    "krong kampot": ("Krong Kampot", "Kampot"),
    "teuk chhou": ("Teuk Chhou", "Kampot"),
    "angkor chey": ("Angkor Chey", "Kampot"),

    # Preah Sihanouk
    "krong preah sihanouk": ("Krong Preah Sihanouk", "Preah Sihanouk"),
    "krong kampong saom": ("Krong Preah Sihanouk", "Preah Sihanouk"),
    "prey nob": ("Prey Nob", "Preah Sihanouk"),
    "otress": ("Krong Preah Sihanouk", "Preah Sihanouk"),
    "koh rong": ("Koh Rong", "Preah Sihanouk"),

    # Siem Reap
    "krong siem reap": ("Krong Siem Reap", "Siem Reap"),
    "prasat bakong": ("Prasat Bakong", "Siem Reap"),
    "banteay srei": ("Banteay Srei", "Siem Reap"),
    "svay leu": ("Svay Leu", "Siem Reap"),

    # Battambang
    "krong battambang": ("Krong Battambang", "Battambang"),
    "thma koul": ("Thma Koul", "Battambang"),
    "moung roussei": ("Moung Ruessei", "Battambang"),

    # Koh Kong
    "khemara phoumin": ("Krong Khemara Phoumin", "Koh Kong"),
    "sre ambel": ("Sre Ambel", "Koh Kong"),
    "botum sakor": ("Botum Sakor", "Koh Kong"),

    # Kampong Speu
    "chbar mon": ("Krong Chbar Mon", "Kampong Speu"),
    "samraong tong": ("Samraong Tong", "Kampong Speu"),
    "kong pisei": ("Kong Pisei", "Kampong Speu"),
    "phnom sruoch": ("Phnom Sruoch", "Kampong Speu"),

    # Stung Treng
    "siem pang": ("Siem Pang", "Stung Treng"),
    "krong stung treng": ("Krong Stung Treng", "Stung Treng"),

    # Ratanakiri
    "banlung": ("Krong Banlung", "Ratanakiri"),
    "krong banlung": ("Krong Banlung", "Ratanakiri"),

    # Kratie
    "krong kratie": ("Krong Kratie", "Kratie"),
    "preaek prasab": ("Preaek Prasab", "Kratie"),
    "prek prasab": ("Preaek Prasab", "Kratie"),
    "ព្រែកប្រសព្វ": ("Preaek Prasab", "Kratie"),
    "saob": ("Preaek Prasab", "Kratie"),
    "សោប": ("Preaek Prasab", "Kratie"),
    "snoul": ("Snoul", "Kratie"),
    "snuol": ("Snoul", "Kratie"),
    "chhlong": ("Chhlong", "Kratie"),
    "sambour": ("Sambour", "Kratie"),
    "chetr borei": ("Chetr Borei", "Kratie"),

    # Banteay Meanchey
    "poipet": ("Poipet", "Banteay Meanchey"),
    "krong serei saophoan": ("Serei Saophoan", "Banteay Meanchey"),

    # Svay Rieng
    "bavet": ("Krong Bavet", "Svay Rieng"),
    "krong svay rieng": ("Krong Svay Rieng", "Svay Rieng"),
}


def clean_price(text: Any) -> Optional[float]:
    """Parse price string into clean float USD."""
    if text is None:
        return None
    if isinstance(text, (int, float)):
        return float(text) if text > 0 else None

    s = str(text).replace(",", "").strip()
    # Check for multiplier like '1.5M' or '250K'
    m_match = re.search(r"\$?\s*([\d.]+)\s*(m|million)", s, re.I)
    if m_match:
        try:
            return round(float(m_match.group(1)) * 1_000_000, 2)
        except ValueError:
            pass

    k_match = re.search(r"\$?\s*([\d.]+)\s*(k|thousand)", s, re.I)
    if k_match:
        try:
            return round(float(k_match.group(1)) * 1_000, 2)
        except ValueError:
            pass

    # Standard numeric extraction
    match = re.search(r"\$?\s*([\d.]+)", s)
    if match:
        try:
            val = float(match.group(1))
            return val if val > 0 else None
        except ValueError:
            pass
    return None


def clean_area(text: Any) -> Optional[float]:
    """Extract area in square meters (m² / sqm / ha)."""
    if text is None:
        return None
    if isinstance(text, (int, float)):
        return float(text) if text > 0 else None

    s = str(text).replace(",", "").strip().lower()

    # Check hectare: 1 ha = 10,000 sqm
    ha_match = re.search(r"([\d.]+)\s*(?:ha|hectare)", s)
    if ha_match:
        try:
            return round(float(ha_match.group(1)) * 10_000, 2)
        except ValueError:
            pass

    # Match m2, sqm, m²
    sqm_match = re.search(r"([\d.]+)\s*(?:m²|m2|sqm|sq\.m)", s)
    if sqm_match:
        try:
            val = float(sqm_match.group(1))
            return val if val > 0 else None
        except ValueError:
            pass

    # Generic number fallback
    num_match = re.search(r"([\d.]+)", s)
    if num_match:
        try:
            val = float(num_match.group(1))
            return val if val > 0 else None
        except ValueError:
            pass
    return None


def normalize_property_type(raw_text: str) -> str:
    """Normalize raw property type text into standard categories."""
    if not raw_text:
        return "Other"
    t = str(raw_text).lower()

    if any(k in t for k in ["land", "plot", "agriculture", "ដី"]):
        return "Land"
    if any(k in t for k in ["condo", "condominium", "studio"]):
        return "Condo"
    if any(k in t for k in ["villa", "twin villa", "queen villa", "king villa", "link house"]):
        return "Villa"
    if any(k in t for k in ["shophouse", "shop house"]):
        return "Shophouse"
    if any(k in t for k in ["flat", "townhouse", "house", "ផ្ទះ"]):
        return "House"
    if any(k in t for k in ["apartment", "serviced apartment"]):
        return "Apartment"
    if any(k in t for k in ["commercial", "office", "retail", "building"]):
        return "Commercial"
    if any(k in t for k in ["warehouse", "factory"]):
        return "Warehouse"
    if "borey" in t:
        return "Borey"
    return "Other"


def extract_location(text: str) -> Dict[str, str]:
    """
    Identify Province, District, and Commune from freeform text or structured address.
    Correctly handles comma-separated formats like:
    - "Spean Mean Chey, Krong Saen Monourom, Mondulkiri"
    - "Krong Saen Monourom, Mondulkiri"
    - "Ta Khmau, Kandal"
    """
    res = {"province": "", "district": "", "commune": ""}
    if not text:
        return res

    cleaned = str(text).strip()

    # 1. Check if structured by comma or •
    parts = [p.strip() for p in re.split(r"[,•|\n]+", cleaned) if p.strip()]
    if len(parts) >= 2:
        last_part = parts[-1].lower()
        matched_prov = None
        for k, v in PROVINCES_MAP.items():
            if k == last_part or last_part.endswith(k) or k in last_part:
                matched_prov = v
                break

        if matched_prov:
            res["province"] = matched_prov
            if len(parts) >= 2:
                res["district"] = parts[-2]
            if len(parts) >= 3:
                res["commune"] = parts[-3]
            return res

    t = cleaned.lower()

    # 2. Check full province map (longest match first)
    sorted_provinces = sorted(PROVINCES_MAP.items(), key=lambda x: len(x[0]), reverse=True)
    for k, v in sorted_provinces:
        if k in t:
            res["province"] = v
            break

    # 3. Check district map (each entry knows its associated province)
    sorted_districts = sorted(DISTRICTS_MAP.items(), key=lambda x: len(x[0]), reverse=True)
    for k, (dist_name, prov_name) in sorted_districts:
        if k in t:
            res["district"] = dist_name
            if not res["province"]:
                res["province"] = prov_name
            break

    return res


def resolve_google_maps_coords(map_url: str, timeout: float = 6.0) -> Tuple[Optional[float], Optional[float]]:
    """
    Extract exact (latitude, longitude) from any Google Maps URL or text.
    Handles:
    - Direct coordinate queries: maps.google.com?q=11.58177,104.90717
    - Path @lat,lng: google.com/maps/@11.58177,104.90717,17z
    - Place coordinates in path: /place/.../@11.58177,104.90717...
    - Shortened redirect URLs: maps.app.goo.gl/XYZ, goo.gl/maps/XYZ
      Resolves redirects and extracts coords from final URL, og:image staticmap, or protobuf markers.
    """
    if not map_url:
        return None, None

    # 1. Quick check: Is lat,lon directly present in the URL?
    q_match = re.search(r"[?&]q=([0-9]{1,2}\.[0-9]{3,15}),([0-9]{2,3}\.[0-9]{3,15})", map_url)
    if q_match:
        try:
            lat = float(q_match.group(1))
            lon = float(q_match.group(2))
            if 8.5 <= lat <= 15.5 and 101.5 <= lon <= 108.5:
                return lat, lon
        except ValueError:
            pass

    at_match = re.search(r"@([0-9]{1,2}\.[0-9]{3,15}),([0-9]{2,3}\.[0-9]{3,15})", map_url)
    if at_match:
        try:
            lat = float(at_match.group(1))
            lon = float(at_match.group(2))
            if 8.5 <= lat <= 15.5 and 101.5 <= lon <= 108.5:
                return lat, lon
        except ValueError:
            pass

    # 2. If it is a shortened or indirect Google Maps link, follow the redirect
    if any(domain in map_url for domain in ["goo.gl", "maps.app.goo.gl", "google.com/maps"]):
        try:
            headers = {
                "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36",
                "Accept-Language": "en-US,en;q=0.9"
            }
            with httpx.Client(headers=headers, follow_redirects=True, timeout=timeout) as client:
                resp = client.get(map_url)
                final_url = str(resp.url)

                # Check final redirected URL for @lat,lon
                at_m = re.search(r"@([0-9]{1,2}\.[0-9]{3,15}),([0-9]{2,3}\.[0-9]{3,15})", final_url)
                if at_m:
                    lat, lon = float(at_m.group(1)), float(at_m.group(2))
                    if 8.5 <= lat <= 15.5 and 101.5 <= lon <= 108.5:
                        return lat, lon

                # Check final redirected URL for ?q=lat,lon
                q_m = re.search(r"[?&]q=([0-9]{1,2}\.[0-9]{3,15}),([0-9]{2,3}\.[0-9]{3,15})", final_url)
                if q_m:
                    lat, lon = float(q_m.group(1)), float(q_m.group(2))
                    if 8.5 <= lat <= 15.5 and 101.5 <= lon <= 108.5:
                        return lat, lon

                # Check staticmap in og:image or meta tags
                center_m = re.search(r"staticmap\?center=([0-9]{1,2}\.[0-9]{3,15})(?:%2C|,)([0-9]{2,3}\.[0-9]{3,15})", resp.text)
                if center_m:
                    lat, lon = float(center_m.group(1)), float(center_m.group(2))
                    if 8.5 <= lat <= 15.5 and 101.5 <= lon <= 108.5:
                        return lat, lon

                # Check protobuf !2dlon!3dlat
                proto_m = re.search(r"!2d([0-9]{2,3}\.[0-9]{3,15})!3d([0-9]{1,2}\.[0-9]{3,15})", resp.text)
                if proto_m:
                    lon, lat = float(proto_m.group(1)), float(proto_m.group(2))
                    if 8.5 <= lat <= 15.5 and 101.5 <= lon <= 108.5:
                        return lat, lon

                # Check protobuf !3dlat!2dlon
                proto_m2 = re.search(r"!3d([0-9]{1,2}\.[0-9]{3,15})!2d([0-9]{2,3}\.[0-9]{3,15})", resp.text)
                if proto_m2:
                    lat, lon = float(proto_m2.group(1)), float(proto_m2.group(2))
                    if 8.5 <= lat <= 15.5 and 101.5 <= lon <= 108.5:
                        return lat, lon

                # Check window.APP_INITIALIZATION_STATE array coordinates
                array_m = re.search(r"\[null,null,([0-9]{1,2}\.[0-9]{4,15}),([0-9]{2,3}\.[0-9]{4,15})\]", resp.text)
                if array_m:
                    lat, lon = float(array_m.group(1)), float(array_m.group(2))
                    if 8.5 <= lat <= 15.5 and 101.5 <= lon <= 108.5:
                        return lat, lon

        except Exception as e:
            print(f"[BaseScraper] Note: Failed to resolve {map_url}: {e}")

    return None, None


def extract_coords_from_text(text: str, resolve_urls: bool = True) -> Tuple[Optional[float], Optional[float]]:
    """
    Extract latitude and longitude coordinates from Google Maps URLs or coordinate strings.
    Supported patterns:
    - maps.app.goo.gl/XYZ, goo.gl/maps/XYZ (resolved to exact GPS)
    - maps.google.com/maps?q=12.468375,107.192041
    - google.com/maps/@12.468375,107.192041
    - lat: 12.468, lon: 107.192
    """
    if not text:
        return None, None

    # 1. Direct Google Maps query: q=12.468375,107.192041
    q_match = re.search(r"[?&]q=([0-9]{1,2}\.[0-9]{3,15}),([0-9]{2,3}\.[0-9]{3,15})", text)
    if q_match:
        try:
            lat = float(q_match.group(1))
            lon = float(q_match.group(2))
            if 8.5 <= lat <= 15.5 and 101.5 <= lon <= 108.5:
                return lat, lon
        except ValueError:
            pass

    # 2. Direct Google Maps @lat,lon
    at_match = re.search(r"@([0-9]{1,2}\.[0-9]{3,15}),([0-9]{2,3}\.[0-9]{3,15})", text)
    if at_match:
        try:
            lat = float(at_match.group(1))
            lon = float(at_match.group(2))
            if 8.5 <= lat <= 15.5 and 101.5 <= lon <= 108.5:
                return lat, lon
        except ValueError:
            pass

    # 3. General coordinates pattern in text (e.g. 11.58177, 104.90717)
    gen_match = re.search(r"\b([0-9]{1,2}\.[0-9]{4,15})[,\s;&]+([0-9]{2,3}\.[0-9]{4,15})\b", text)
    if gen_match:
        try:
            lat = float(gen_match.group(1))
            lon = float(gen_match.group(2))
            if 8.5 <= lat <= 15.5 and 101.5 <= lon <= 108.5:
                return lat, lon
        except ValueError:
            pass

    # 4. If URL present in text and resolve_urls is enabled, search for Google Maps link and resolve it
    if resolve_urls and ("goo.gl" in text or "google.com/maps" in text):
        url_match = re.search(r"https?://[^\s\"\'<>]+(?:maps\.app\.goo\.gl|goo\.gl/maps|google\.com/maps)[^\s\"\'<>]*", text)
        if url_match:
            clean_url = re.sub(r"[,;.!?\)\\]+$", "", url_match.group(0))
            lat, lon = resolve_google_maps_coords(clean_url)
            if lat and lon:
                return lat, lon

    return None, None


def extract_urgency_tag(text: str) -> str:
    """Check if listing has urgency badges or discount markers."""
    if not text:
        return ""
    t = str(text).lower()
    if any(w in t for w in ["urgent sale", "urgent", "បន្ទាន់", "fire sale"]):
        return "Urgent Sale"
    if any(w in t for w in ["under market value", "below market", "below cost"]):
        return "Below Market"
    if any(w in t for w in ["hot offer", "special price", "discount"]):
        return "Special Offer"
    if any(w in t for w in ["negotiable", "ចរចា"]):
        return "Negotiable"
    return ""
