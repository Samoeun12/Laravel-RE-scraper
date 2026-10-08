"""
Configuration settings for Cambodia Real Estate Scraper & Valuation Tool.
"""

import os
from pathlib import Path

# Base directory
BASE_DIR = Path(__file__).resolve().parent

# Database path
DB_PATH = os.environ.get("RE_DB_PATH", str(BASE_DIR / "properties.db"))

# Supported Target Websites
WEBSITES = {
    "arc": {
        "name": "ARC (Asia Real Estate)",
        "url": "https://arc.com.kh/",
        "method": "REST API",
        "speed": "Ultra Fast",
        "color": "#2563eb",
        "icon": "fa-solid fa-city"
    },
    "harbor": {
        "name": "Harbor Property",
        "url": "https://www.harbor-property.com/en/",
        "method": "REST API",
        "speed": "Fast",
        "color": "#059669",
        "icon": "fa-solid fa-anchor"
    },
    "realestate": {
        "name": "Realestate.com.kh",
        "url": "https://www.realestate.com.kh/",
        "method": "REST API & Next.js",
        "speed": "Ultra Fast",
        "color": "#dc2626",
        "icon": "fa-solid fa-house-chimney"
    },
    "khmer24": {
        "name": "Khmer24",
        "url": "https://www.khmer24.com/en/",
        "method": "Playwright Browser",
        "speed": "Moderate",
        "color": "#d97706",
        "icon": "fa-solid fa-store"
    },
    "cambodia_re": {
        "name": "Cambodia Real Estate (C21)",
        "url": "https://cambodia-real-estate.com/",
        "method": "Direct REST API",
        "speed": "Ultra Fast",
        "color": "#7c3aed",
        "icon": "fa-solid fa-building-shield"
    },
    "bayon": {
        "name": "Bayon App Real Estate",
        "url": "https://bayonapp.com/",
        "method": "Direct REST API",
        "speed": "Ultra Fast",
        "color": "#f97316",
        "icon": "fa-solid fa-mobile-screen-button"
    }
}

# Standard Property Categories
PROPERTY_TYPES = [
    "All",
    "Land",
    "Condo",
    "Villa",
    "House",
    "Shophouse",
    "Apartment",
    "Commercial",
    "Warehouse",
    "Other"
]

# Major Cambodian Provinces & Cities (All 25 Provinces)
PROVINCES = [
    "All",
    "Phnom Penh",
    "Siem Reap",
    "Preah Sihanouk",
    "Kandal",
    "Kampot",
    "Battambang",
    "Kep",
    "Kampong Cham",
    "Koh Kong",
    "Kampong Speu",
    "Takeo",
    "Kampong Chhnang",
    "Kampong Thom",
    "Prey Veng",
    "Svay Rieng",
    "Pursat",
    "Banteay Meanchey",
    "Pailin",
    "Kratie",
    "Stung Treng",
    "Ratanakiri",
    "Mondulkiri",
    "Preah Vihear",
    "Oddar Meanchey",
    "Tboung Khmum",
    "Other"
]

# Popular Districts in Phnom Penh
PHNOM_PENH_DISTRICTS = [
    "Chamkarmon",
    "Boeng Keng Kang (BKK)",
    "Daun Penh",
    "Toul Kork",
    "Sen Sok",
    "Chroy Changvar",
    "Mean Chey",
    "Chbar Ampov",
    "Russey Keo",
    "Por Sen Chey",
    "Dangkao",
    "Prek Pnov",
    "Kamboul"
]

# Default HTTP Request Headers
DEFAULT_HEADERS = {
    "User-Agent": (
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
        "(KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36"
    ),
    "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,application/json,*/*;q=0.8",
    "Accept-Language": "en-US,en;q=0.9,km;q=0.8",
}

# CMA Default Tolerances
CMA_SIZE_TOLERANCE_PCT = 0.25 # +/- 25% area for comparables
DEAL_DISCOUNT_THRESHOLD_GOOD = 0.15 # 15% below median
DEAL_DISCOUNT_THRESHOLD_HOT = 0.30 # 30% below median
