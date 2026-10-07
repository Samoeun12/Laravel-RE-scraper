"""
Geographic Geocoding & Interactive Map Visualization Engine for Cambodia Real Estate.
Provides bilingual district extraction, coordinate positioning with jittering,
and interactive Leaflet/OpenStreetMap rendering.
"""

import json
import random
import re
from typing import Dict, Any, Tuple, Optional
import pandas as pd

# Coordinates for all 14 Phnom Penh Khan Districts
PHNOM_PENH_DISTRICT_COORDS: Dict[str, Tuple[float, float]] = {
    "Daun Penh": (11.5725, 104.9250),
    "Chamkarmon": (11.5434, 104.9220),
    "Boeng Keng Kang": (11.5505, 104.9265),
    "Boeng Keng Kang (BKK)": (11.5505, 104.9265),
    "BKK": (11.5505, 104.9265),
    "Toul Kork": (11.5732, 104.8988),
    "Sen Sok": (11.5830, 104.8624),
    "Chroy Changvar": (11.5973, 104.9392),
    "Mean Chey": (11.5204, 104.9085),
    "Chbar Ampov": (11.5292, 104.9567),
    "Russey Keo": (11.6111, 104.9083),
    "Por Sen Chey": (11.5539, 104.8214),
    "Dangkao": (11.4789, 104.8722),
    "Prek Pnov": (11.6508, 104.8389),
    "Kamboul": (11.5250, 104.7500),
}

# Coordinates for Key Provincial Districts / Krongs
PROVINCIAL_DISTRICT_COORDS: Dict[str, Tuple[float, float]] = {
    "Krong Saen Monourom": (12.4558, 107.1881),
    "Saen Monourom": (12.4558, 107.1881),
    "Sen Monorom": (12.4558, 107.1881),
    "Senmonorom": (12.4558, 107.1881),
    "Spean Mean Chey": (12.4580, 107.1900),
    "Ta Khmau": (11.4833, 104.9500),
    "Angk Snuol": (11.5167, 104.7333),
    "Kien Svay": (11.4833, 105.0667),
    "Chhuk": (10.8833, 104.4500),
    "Krong Kampot": (10.6104, 104.1815),
    "Krong Preah Sihanouk": (10.6275, 103.5221),
    "Krong Siem Reap": (13.3671, 103.8448),
    "Poipet": (13.6561, 102.5636),
    "Krong Battambang": (13.0957, 103.2022),
    "Bavet": (11.0828, 106.1439),
    "Krong Chbar Mon": (11.4533, 104.5209),
    "Krong Banlung": (13.7394, 106.9873),
    "Krong Stung Treng": (13.5259, 105.9683),
    "Krong Kratie": (12.4881, 106.0188),
    "Preaek Prasab": (12.4293, 105.9858),
    "Prek Prasab": (12.4293, 105.9858),
    "Saob": (12.4293, 105.9858),
}

# Coordinates for all Cambodian Provinces
PROVINCE_COORDS: Dict[str, Tuple[float, float]] = {
    "Phnom Penh": (11.5564, 104.9282),
    "Siem Reap": (13.3671, 103.8448),
    "Preah Sihanouk": (10.6275, 103.5221),
    "Kandal": (11.4550, 104.9810),
    "Kampot": (10.6104, 104.1815),
    "Battambang": (13.0957, 103.2022),
    "Kep": (10.4829, 104.3167),
    "Kampong Cham": (11.9924, 105.4645),
    "Koh Kong": (11.6153, 102.9838),
    "Kampong Speu": (11.4533, 104.5209),
    "Takeo": (10.9908, 104.7849),
    "Kampong Chhnang": (12.2500, 104.6667),
    "Kampong Thom": (12.7111, 104.8887),
    "Prey Veng": (11.4868, 105.3253),
    "Svay Rieng": (11.0879, 105.7994),
    "Pursat": (12.5388, 103.9192),
    "Banteay Meanchey": (13.5859, 102.9737),
    "Pailin": (12.8489, 102.6093),
    "Kratie": (12.4881, 106.0188),
    "Stung Treng": (13.5259, 105.9683),
    "Ratanakiri": (13.7394, 106.9873),
    "Mondulkiri": (12.4558, 107.1881),
    "Preah Vihear": (13.8073, 104.9805),
    "Oddar Meanchey": (14.1818, 103.5176),
    "Tboung Khmum": (11.8891, 105.6593),
}

# Khmer keyword mapping for district extraction
KHMER_DISTRICT_PATTERNS = [
    (r"ទួលគោក|Toul\s*Kork|Tuol\s*Kouk", "Toul Kork"),
    (r"ដូនពេញ|Daun\s*Penh|Doun\s*Penh", "Daun Penh"),
    (r"ចំការមន|Chamkarmon|Chamkarmorn", "Chamkarmon"),
    (r"បឹងកេងកង|Boeng\s*Keng\s*Kang|BKK", "Boeng Keng Kang"),
    (r"សែនសុខ|Sen\s*Sok|Sensok", "Sen Sok"),
    (r"ច្បារអំពៅ|Chbar\s*Ampov|Chbar\s*Ampov", "Chbar Ampov"),
    (r"ខណ្ឌមានជ័យ|Khan\s*Mean\s*Chey", "Mean Chey"),
    (r"ជ្រោយចង្វារ|Chroy\s*Changvar|Chroy\s*Changva", "Chroy Changvar"),
    (r"ឫស្សីកែវ|ឬស្សីកែវ|Russey\s*Keo|Russei\s*Keo", "Russey Keo"),
    (r"ពោធិ៍សែនជ័យ|ពោធិសែនជ័យ|Por\s*Sen\s*Chey|Pur\s*Senchey", "Por Sen Chey"),
    (r"ដង្កោ|Dangkao|Dangkor", "Dangkao"),
    (r"ព្រែកព្នៅ|Prek\s*Pnov|Praek\s*Pnov", "Prek Pnov"),
    (r"កំបូល|Kamboul|Kambol", "Kamboul"),
    (r"សែនមនោរម្យ|Saen\s*Monourom|Sen\s*Monorom", "Krong Saen Monourom"),
    (r"ស្ពានមានជ័យ|Spean\s*Mean\s*Chey", "Krong Saen Monourom"),
    (r"តាខ្មៅ|Ta\s*Khmau", "Ta Khmau"),
    (r"ឈូក|Chhuk", "Chhuk"),
    (r"ប៉ោយប៉ែត|Poipet", "Poipet"),
]

PROPERTY_TYPE_COLORS = {
    "Land": "#10b981",       # Emerald
    "Condo": "#38bdf8",      # Cyan
    "Villa": "#a855f7",      # Purple
    "House": "#f59e0b",      # Amber
    "Shophouse": "#ec4899",  # Pink
    "Apartment": "#06b6d4",  # Teal
    "Commercial": "#6366f1", # Indigo
    "Warehouse": "#64748b",  # Slate
    "Borey": "#14b8a6",      # Teal-Green
    "Other": "#94a3b8"       # Gray
}


def extract_district(text: str) -> Optional[str]:
    """Extract known district name from multilingual text."""
    if not text or not isinstance(text, str):
        return None
    for pattern, name in KHMER_DISTRICT_PATTERNS:
        if re.search(pattern, text, re.IGNORECASE):
            return name
    return None


def get_base_coordinates(province: str, district: str) -> Tuple[float, float, float]:
    """
    Returns (latitude, longitude, radius_km) for a given province and district.
    """
    # 1. Check District level in Phnom Penh
    if district:
        for d_key, coords in PHNOM_PENH_DISTRICT_COORDS.items():
            if d_key.lower() == district.lower() or d_key.lower() in district.lower():
                return coords[0], coords[1], 1.8

        for d_key, coords in PROVINCIAL_DISTRICT_COORDS.items():
            if d_key.lower() == district.lower() or d_key.lower() in district.lower():
                return coords[0], coords[1], 2.0

    # 2. Check Province level
    if province:
        for p_key, coords in PROVINCE_COORDS.items():
            if p_key.lower() in province.lower() or province.lower() in p_key.lower():
                radius = 6.0 if "phnom" in province.lower() else 8.0
                return coords[0], coords[1], radius

    # Default fallback: Phnom Penh Center
    return 11.5564, 104.9282, 8.0


def geocode_properties(df: pd.DataFrame) -> pd.DataFrame:
    """
    Assigns realistic coordinates to every listing.
    Uses exact scraped GPS coordinates (latitude/longitude) if present,
    or falls back to district/province centroid with jittering.
    """
    if df.empty:
        df["lat"] = 11.5564
        df["lon"] = 104.9282
        df["is_exact_gps"] = False
        return df

    df = df.copy()

    lats = []
    lons = []
    is_exact_list = []

    for _, row in df.iterrows():
        # 1. First priority: Check if exact scraped GPS coordinates exist
        lat_val = row.get("latitude")
        lon_val = row.get("longitude")
        if pd.notnull(lat_val) and pd.notnull(lon_val):
            try:
                lat_f = float(lat_val)
                lon_f = float(lon_val)
                if 9.5 <= lat_f <= 15.0 and 102.0 <= lon_f <= 108.0:
                    lats.append(round(lat_f, 6))
                    lons.append(round(lon_f, 6))
                    is_exact_list.append(True)
                    continue
            except (ValueError, TypeError):
                pass

        # 2. Fallback: Base coordinates from district/province with deterministic jitter
        prov = str(row.get("province") or "")
        dist = str(row.get("district") or "")

        if not dist or dist.lower() in ["none", "nan", ""]:
            extracted = extract_district(f"{row.get('title', '')} {row.get('address', '')}")
            if extracted:
                dist = extracted

        base_lat, base_lon, radius_km = get_base_coordinates(prov, dist)

        prop_id = row.get("id", 0)
        rng = random.Random(int(prop_id) if prop_id else 42)

        deg_radius = (radius_km / 111.0) * 0.75
        u = rng.random()
        v = rng.random()
        w = deg_radius * (u ** 0.5)
        jitter_lat = w * (0.85 * rng.choice([-1, 1]))
        jitter_lon = w * (1.15 * rng.choice([-1, 1]))

        lats.append(round(base_lat + jitter_lat, 6))
        lons.append(round(base_lon + jitter_lon, 6))
        is_exact_list.append(False)

    df["lat"] = lats
    df["lon"] = lons
    df["is_exact_gps"] = is_exact_list
    return df


def generate_leaflet_map_html(df: pd.DataFrame, height_px: int = 620, max_pins: Optional[int] = None) -> str:
    """
    Generates a standalone, dark-themed Leaflet/OpenStreetMap HTML widget
    with custom colored pin markers, price tags, high-res popup cards,
    and marker clustering.
    """
    if df.empty:
        return "<div style='color:#94a3b8; padding:30px; text-align:center;'>No listings available to display on map.</div>"

    # Limit to max_pins if specified; otherwise render all mapped properties
    if max_pins is not None and max_pins > 0 and len(df) > max_pins:
        map_df = df.head(max_pins).copy()
    else:
        map_df = df.copy()

    # Calculate center coordinates
    center_lat = float(map_df["lat"].median())
    center_lon = float(map_df["lon"].median())

    # Build markers JSON payload
    markers = []
    for _, row in map_df.iterrows():
        ptype = str(row.get("property_type") or "Other")
        ltype = str(row.get("listing_type") or "Sale")
        color = "#ef4444" if row.get("urgency_tag") else PROPERTY_TYPE_COLORS.get(ptype, "#38bdf8")
        price = float(row.get("price_usd") or 0)
        pp_sqm = float(row.get("price_per_sqm") or 0) if pd.notnull(row.get("price_per_sqm")) else 0
        area = float(row.get("area_sqm") or 0) if pd.notnull(row.get("area_sqm")) else 0

        # Distinct price formatting for Rent ($/mo) vs Sale (Total $)
        if ltype == "Rent":
            price_str = f"${price:,.0f}/mo" if price > 0 else "For Rent"
            price_full = f"${price:,.0f} / month" if price > 0 else "For Rent"
            price_pp_sqm = f"${pp_sqm:,.2f}/m²/mo" if pp_sqm > 0 else ""
        else:
            if price >= 1_000_000:
                price_str = f"${price / 1_000_000:.1f}M"
            elif price >= 1_000:
                price_str = f"${price / 1_000:.0f}K"
            elif price > 0:
                price_str = f"${price:,.0f}"
            else:
                price_str = "Price N/A"
            price_full = f"${price:,.0f}" if price > 0 else "Contact for Price"
            price_pp_sqm = f"${pp_sqm:,.1f}/m²" if pp_sqm > 0 else ""

        markers.append({
            "id": int(row.get("id", 0)),
            "lat": float(row["lat"]),
            "lon": float(row["lon"]),
            "title": str(row.get("title", "")).replace('"', '&quot;')[:75],
            "price": price_str,
            "price_full": price_full,
            "price_pp_sqm": price_pp_sqm,
            "area": f"{area:,.0f} m²" if area > 0 else "N/A",
            "type": ptype,
            "listing_type": ltype,
            "color": color,
            "location": f"{row.get('district') or ''} {row.get('province') or 'Cambodia'}".strip(),
            "source": str(row.get("source", "")).upper(),
            "url": str(row.get("url", "#")),
            "image": str(row.get("image_url") or ""),
            "urgent": str(row.get("urgency_tag") or ""),
            "is_exact_gps": bool(row.get("is_exact_gps", False))
        })

    markers_json = json.dumps(markers)

    html_template = f"""
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <!-- Leaflet CSS -->
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <!-- MarkerCluster CSS -->
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
        <!-- Font Awesome & Siemreap Khmer Font -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Siemreap&display=swap" />

        <style>
            html, body {{
                margin: 0;
                padding: 0;
                height: 100%;
                width: 100%;
                background-color: #08090d;
                font-family: 'Siemreap', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            }}
            #map {{
                width: 100%;
                height: {height_px}px;
                border-radius: 12px;
                border: 1px solid rgba(255,255,255,0.06);
                box-shadow: 0 4px 20px rgba(0,0,0,0.4);
            }}

            /* Custom Price Tag Pin — Dark Neon */
            .price-pin {{
                display: inline-flex;
                align-items: center;
                gap: 4px;
                background: rgba(8, 9, 13, 0.9);
                color: #00d4ff;
                font-size: 11px;
                font-weight: 700;
                padding: 3px 8px;
                border-radius: 20px;
                border: 1.5px solid rgba(0,212,255,0.35);
                box-shadow: 0 0 12px rgba(0,212,255,0.1), 0 2px 8px rgba(0,0,0,0.3);
                white-space: nowrap;
                transition: transform 0.15s ease, box-shadow 0.15s ease;
                cursor: pointer;
            }}
            .price-pin:hover {{
                transform: scale(1.12) translateY(-2px);
                box-shadow: 0 0 20px rgba(0,212,255,0.2), 0 6px 16px rgba(0,0,0,0.4);
                z-index: 9999 !important;
            }}
            .price-pin-dot {{
                width: 7px;
                height: 7px;
                border-radius: 50%;
            }}

            /* Leaflet Popup — Dark Glass */
            .leaflet-popup-content-wrapper {{
                background: rgba(17, 19, 24, 0.95) !important;
                color: #e2e8f0 !important;
                border: 1px solid rgba(255,255,255,0.08) !important;
                border-radius: 12px !important;
                padding: 0 !important;
                overflow: hidden !important;
                box-shadow: 0 16px 40px rgba(0,0,0,0.5) !important;
                backdrop-filter: blur(16px) !important;
            }}
            .leaflet-popup-content {{
                margin: 0 !important;
                line-height: 1.3 !important;
                width: 250px !important;
            }}
            .leaflet-popup-tip {{
                background: rgba(17, 19, 24, 0.95) !important;
                border: 1px solid rgba(255,255,255,0.08) !important;
            }}
            .popup-img-box {{
                width: 100%;
                height: 120px;
                overflow: hidden;
                position: relative;
                background: rgba(25,28,36,0.8);
            }}
            .popup-img {{
                width: 100%;
                height: 100%;
                object-fit: cover;
            }}
            .popup-tag {{
                position: absolute;
                top: 6px;
                left: 6px;
                background: rgba(8, 9, 13, 0.85);
                border: 1px solid rgba(255,255,255,0.1);
                color: #00d4ff;
                font-size: 10px;
                font-weight: 700;
                padding: 2px 6px;
                border-radius: 4px;
                backdrop-filter: blur(6px);
            }}
            .popup-urgent {{
                position: absolute;
                top: 6px;
                right: 6px;
                background: linear-gradient(135deg, #ff006e, #ff3388);
                color: #fff;
                font-size: 10px;
                font-weight: 700;
                padding: 2px 6px;
                border-radius: 4px;
                box-shadow: 0 0 8px rgba(255,0,110,0.3);
            }}
            .popup-body {{
                padding: 10px 12px 12px 12px;
            }}
            .popup-title {{
                font-size: 13px;
                font-weight: 600;
                color: #e2e8f0;
                margin-bottom: 4px;
                max-height: 34px;
                overflow: hidden;
            }}
            .popup-price {{
                font-size: 16px;
                font-weight: 800;
                color: #00ff88;
            }}
            .popup-meta {{
                font-size: 11.5px;
                color: #64748b;
                margin-top: 4px;
                display: flex;
                gap: 8px;
            }}
            .popup-btn {{
                display: block;
                text-align: center;
                background: linear-gradient(135deg, #00d4ff, #0099cc);
                color: #08090d !important;
                text-decoration: none;
                font-size: 11.5px;
                font-weight: 700;
                padding: 6px 0;
                border-radius: 6px;
                margin-top: 8px;
                transition: all 0.15s ease;
                box-shadow: 0 0 12px rgba(0,212,255,0.15);
            }}
            .popup-btn:hover {{
                background: linear-gradient(135deg, #33dfff, #00b8e6);
                box-shadow: 0 0 20px rgba(0,212,255,0.25);
            }}

            /* Marker Cluster — Neon Dark */
            .marker-cluster-small, .marker-cluster-medium, .marker-cluster-large {{
                background-color: rgba(0, 212, 255, 0.12) !important;
            }}
            .marker-cluster-small div, .marker-cluster-medium div, .marker-cluster-large div {{
                background-color: rgba(0, 212, 255, 0.8) !important;
                color: #08090d !important;
                font-weight: 700 !important;
                border: 2px solid rgba(0,212,255,0.4) !important;
                box-shadow: 0 0 12px rgba(0,212,255,0.2), 0 2px 8px rgba(0,0,0,0.3) !important;
            }}
        </style>
    </head>
    <body>
        <div id="map"></div>

        <!-- Leaflet JS -->
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <!-- MarkerCluster JS -->
        <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

        <script>
            const properties = {markers_json};

            // Initialize map with center and clean light tiles
            const map = L.map('map', {{
                center: [{center_lat}, {center_lon}],
                zoom: 12,
                zoomControl: true,
                attributionControl: false
            }});

            // Base Tile Layers (100% Free, No Watermark, No API Key Required)
            const osm = L.tileLayer('https://tile.openstreetmap.org/{{z}}/{{x}}/{{y}}.png', {{
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }});

            const lightCanvas = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{{z}}/{{y}}/{{x}}', {{
                maxZoom: 16,
                attribution: 'Esri Light'
            }});

            const lightLabels = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Reference/MapServer/tile/{{z}}/{{y}}/{{x}}', {{
                maxZoom: 16
            }});

            const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{{z}}/{{y}}/{{x}}', {{
                maxZoom: 19,
                attribution: 'Esri Satellite'
            }});

            const darkBase = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Dark_Gray_Base/MapServer/tile/{{z}}/{{y}}/{{x}}', {{
                maxZoom: 16,
                attribution: 'Esri Dark'
            }});

            const darkLabels = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Dark_Gray_Reference/MapServer/tile/{{z}}/{{y}}/{{x}}', {{
                maxZoom: 16
            }});

            // Default Dark Mode Map
            darkBase.addTo(map);
            darkLabels.addTo(map);

            // Layer Switcher Control
            const baseLayers = {{
                "🗺️ Street Map (OSM)": osm,
                "☀️ Light Gray Canvas": lightCanvas,
                "🛰️ Satellite Aerial": satellite,
                "🌙 Dark Mode": darkBase
            }};
            L.control.layers(baseLayers, null, {{ position: 'topright' }}).addTo(map);

            // Keep reference overlay synced with selected basemap
            map.on('baselayerchange', function(e) {{
                map.removeLayer(lightLabels);
                map.removeLayer(darkLabels);
                if (e.name === '🌙 Dark Mode') {{
                    map.addLayer(darkLabels);
                }} else if (e.name === '☀️ Light Gray Canvas') {{
                    map.addLayer(lightLabels);
                }}
            }});

            // Marker cluster group with smooth anim
            const clusterGroup = L.markerClusterGroup({{
                showCoverageOnHover: false,
                maxClusterRadius: 35,
                spiderfyOnMaxZoom: true
            }});

            properties.forEach(p => {{
                // Create custom HTML Price Tag Pin icon
                const iconHtml = `
                    <div class="price-pin" style="border-color: ${{p.color}};">
                        <span class="price-pin-dot" style="background: ${{p.color}};"></span>
                        <span>${{p.price}}</span>
                    </div>
                `;

                const customIcon = L.divIcon({{
                    className: 'custom-leaflet-pin',
                    html: iconHtml,
                    iconSize: [60, 24],
                    iconAnchor: [30, 12],
                    popupAnchor: [0, -14]
                }});

                const marker = L.marker([p.lat, p.lon], {{ icon: customIcon }});

                // Image fallback
                const fallbackImg = "https://images.unsplash.com/photo-1580587771525-78b9dba3b914?w=500&auto=format&fit=crop&q=80";
                const imgTag = p.image && p.image.startsWith('http')
                    ? `<img src="${{p.image}}" onerror="this.onerror=null;this.src='${{fallbackImg}}';" class="popup-img" alt="thumb"/>`
                    : `<img src="${{fallbackImg}}" class="popup-img" alt="thumb"/>`;

                const urgentHtml = p.urgent ? `<div class="popup-urgent"><i class="fa-solid fa-fire"></i> ${{p.urgent}}</div>` : '';
                const gpsBadge = p.is_exact_gps ? `<span style="color:#10b981; font-weight:600; font-size:10px; margin-left:4px;"><i class="fa-solid fa-location-crosshairs"></i> GPS</span>` : '';

                const ltypeBadge = p.listing_type === 'Rent'
                    ? `<div class="popup-tag" style="left:auto; right:6px; background:rgba(0,212,255,0.2); color:#00d4ff; border:1px solid rgba(0,212,255,0.3); font-weight:700;">FOR RENT</div>`
                    : `<div class="popup-tag" style="left:auto; right:6px; background:rgba(0,255,136,0.2); color:#00ff88; border:1px solid rgba(0,255,136,0.3); font-weight:700;">FOR SALE</div>`;

                const popupHtml = `
                    <div class="popup-img-box">
                        ${{imgTag}}
                        <div class="popup-tag">${{p.type}}</div>
                        ${{ltypeBadge}}
                    </div>
                    <div class="popup-body">
                        <div class="popup-title">${{p.title}}</div>
                        <div class="popup-price">${{p.price_full}} <span style="font-size:11px; color:#94a3b8; font-weight:normal;">${{p.price_pp_sqm}}</span></div>
                        <div class="popup-meta">
                            <span><i class="fa-solid fa-location-dot" style="color:#38bdf8;"></i> ${{p.location}} ${{gpsBadge}}</span>
                            <span><i class="fa-solid fa-ruler-combined" style="color:#a855f7;"></i> ${{p.area}}</span>
                        </div>
                        <a href="${{p.url}}" target="_blank" class="popup-btn">
                            View Listing <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:10px;"></i>
                        </a>
                    </div>
                `;

                marker.bindPopup(popupHtml);
                clusterGroup.addLayer(marker);
            }});

            map.addLayer(clusterGroup);

            // Fit bounds to listings if properties exist
            if (properties.length > 1) {{
                const bounds = L.latLngBounds(properties.map(p => [p.lat, p.lon]));
                map.fitBounds(bounds, {{ padding: [30, 30], maxZoom: 15 }});
            }}
        </script>
    </body>
    </html>
    """
    return html_template
