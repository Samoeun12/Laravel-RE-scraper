"""
Cambodia Real Estate Property Scraper, CMA, Deal Finder, and Land Price Estimator.
Enterprise MLS Intelligence Portal - Main Application.
"""

import sys
import io
import os
import asyncio
from datetime import datetime
from typing import Any, Optional, Tuple, Dict, List

# Ensure proper encoding
sys.stdout.reconfigure(encoding="utf-8")

# Fix Windows asyncio subprocess NotImplementedError when Playwright is invoked
if sys.platform == "win32":
    try:
        asyncio.set_event_loop_policy(asyncio.WindowsProactorEventLoopPolicy())
    except Exception:
        pass

import streamlit as st
import pandas as pd
import numpy as np
import streamlit.components.v1 as components

from config import WEBSITES, PROPERTY_TYPES, PROVINCES, PHNOM_PENH_DISTRICTS
from database import (
    init_db,
    query_properties,
    get_summary_stats,
    get_connection,
    get_scrape_logs,
    get_recent_properties,
    get_database_telemetry,
    vacuum_database,
    cleanup_duplicate_properties,
    DB_PATH
)
from scrapers.scraper_manager import ScraperManager
from analytics.cma_engine import generate_cma_report
from analytics.deal_finder import find_good_deals
from analytics.land_estimator import estimate_land_price, get_district_land_benchmarks
from analytics.map_engine import geocode_properties, generate_leaflet_map_html, PROPERTY_TYPE_COLORS

# Page Configuration
st.set_page_config(
    page_title="Cambodia PropTech MLS Intelligence Portal",
    page_icon="🏢",
    layout="wide",
    initial_sidebar_state="expanded"
)

# Custom Styling with Plus Jakarta Sans & Kantumruy Pro & Font Awesome 6
st.markdown("""
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300..800;1,300..800&family=Kantumruy+Pro:ital,wght@0,100..700;1,100..700&family=Inter:wght@300;400;500;600;700;800&display=swap">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300..800;1,300..800&family=Kantumruy+Pro:ital,wght@0,100..700;1,100..700&family=Inter:wght@300;400;500;600;700;800&display=swap');

    /* ═══════════════════════════════════════════════════════
       DARK GLASSMORPHISM DESIGN SYSTEM — NEON TERMINAL
       Color Tokens:
         --bg-deep:    #08090d    (canvas)
         --bg-card:    rgba(17,19,24,0.85)  (frosted glass cards)
         --bg-elevated: rgba(25,28,36,0.9)  (elevated surfaces)
         --border:     rgba(255,255,255,0.06)
         --border-glow: rgba(0,212,255,0.15)
         --neon-cyan:  #00d4ff    (primary accent)
         --neon-green: #00ff88    (success / positive)
         --neon-pink:  #ff006e    (alert / critical)
         --neon-purple:#a855f7    (secondary highlight)
         --text-primary: #e2e8f0
         --text-secondary: #8892a4
         --text-muted: #4a5568
    ═══════════════════════════════════════════════════════ */

    /* Global Typography Reset & Antialiasing */
    html, body, .stApp, 
    div, p, span, a, label, b, strong, h1, h2, h3, h4, h5, h6, 
    input, select, textarea, button,
    [data-testid="stMarkdownContainer"], [data-testid="stMarkdownContainer"] *,
    [data-testid="stMetricValue"], [data-testid="stMetricLabel"],
    [data-testid="stDataFrame"], [data-testid="stDataFrame"] *,
    [data-testid="stTable"], [data-testid="stTable"] *,
    [data-testid="stSelectbox"], [data-testid="stSelectbox"] *,
    [data-baseweb="select"], [data-baseweb="select"] *,
    [data-baseweb="tab"], [data-baseweb="tab"] *,
    .prop-card, .prop-card *, .prop-title, .prop-price, .prop-meta, .prop-meta *,
    .tab-hero, .tab-hero * {
        font-family: 'Plus Jakarta Sans', 'Inter', 'Kantumruy Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
        -webkit-font-smoothing: antialiased !important;
        -moz-osx-font-smoothing: grayscale !important;
    }

    /* Khmer typography optimization */
    .khmer-text, .prop-title, .grid-title-text {
        font-family: 'Kantumruy Pro', 'Plus Jakarta Sans', sans-serif !important;
    }

    /* ═══ DARK CANVAS BACKGROUND ═══ */
    .stApp {
        background-color: #08090d !important;
        background-image: 
            radial-gradient(ellipse at 0% 0%, rgba(0, 212, 255, 0.03) 0px, transparent 50%),
            radial-gradient(ellipse at 100% 0%, rgba(168, 85, 247, 0.03) 0px, transparent 50%),
            radial-gradient(ellipse at 50% 100%, rgba(0, 255, 136, 0.02) 0px, transparent 50%) !important;
        color: #e2e8f0 !important;
    }

    /* ═══ HEADINGS & TYPOGRAPHY ═══ */
    h1, h2, h3, h4, h5, h6 {
        color: #f1f5f9 !important;
        font-weight: 700 !important;
        letter-spacing: -0.025em !important;
    }
    p, span, label, div {
        color: #94a3b8;
    }
    label[data-testid="stWidgetLabel"] p {
        color: #e2e8f0 !important;
        font-weight: 600 !important;
        font-size: 13px !important;
        letter-spacing: -0.01em !important;
    }

    /* ═══ SIDEBAR — DARK GLASS ═══ */
    section[data-testid="stSidebar"] {
        background-color: #0d0e13 !important;
        border-right: 1px solid rgba(255,255,255,0.05) !important;
        box-shadow: 1px 0 20px rgba(0, 0, 0, 0.4) !important;
    }
    section[data-testid="stSidebar"] [data-testid="stMarkdownContainer"] p,
    section[data-testid="stSidebar"] div,
    section[data-testid="stSidebar"] span {
        color: #8892a4;
    }
    .sidebar-brand {
        display: flex;
        align-items: center;
        gap: 12px;
        padding-bottom: 16px;
        margin-bottom: 16px;
        border-bottom: 1px solid rgba(255,255,255,0.06);
    }
    .sidebar-stat-card {
        background: rgba(17,19,24,0.8) !important;
        border: 1px solid rgba(255,255,255,0.06) !important;
        border-radius: 10px !important;
        padding: 10px 14px !important;
        margin-bottom: 8px !important;
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3) !important;
        transition: all 0.2s ease !important;
        backdrop-filter: blur(12px) !important;
    }
    .sidebar-stat-card:hover {
        background: rgba(25,28,36,0.9) !important;
        border-color: rgba(0,212,255,0.15) !important;
        box-shadow: 0 0 15px rgba(0,212,255,0.06) !important;
    }
    .sidebar-stat-icon {
        width: 34px;
        height: 34px;
        min-width: 34px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
    }
    .portal-stat-pill {
        background: rgba(17,19,24,0.8) !important;
        border: 1px solid rgba(255,255,255,0.06) !important;
        border-radius: 8px !important;
        padding: 8px 12px !important;
        margin-bottom: 6px !important;
        display: flex !important;
        flex-direction: column !important;
        gap: 6px !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25) !important;
        transition: all 0.2s ease !important;
    }
    .portal-stat-pill:hover {
        background: rgba(25,28,36,0.95) !important;
        border-color: rgba(0,212,255,0.12) !important;
    }

    /* ═══ SIDEBAR NAVIGATION PILLS — NEON GLOW ═══ */
    div[data-testid="stSidebar"] div[role="radiogroup"] > label > div:first-of-type:not([data-testid="stMarkdownContainer"]),
    div[data-testid="stSidebar"] div[role="radiogroup"] > label > div:not(:has([data-testid="stMarkdownContainer"])):not([data-testid="stMarkdownContainer"]),
    div[data-testid="stSidebar"] div[role="radiogroup"] > label input {
        display: none !important;
    }
    div[data-testid="stSidebar"] div[role="radiogroup"] > label {
        background: rgba(17,19,24,0.7) !important;
        border: 1px solid rgba(255,255,255,0.05) !important;
        border-radius: 10px !important;
        padding: 9px 14px !important;
        margin-bottom: 5px !important;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        cursor: pointer !important;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.2) !important;
    }
    div[data-testid="stSidebar"] div[role="radiogroup"] > label:hover {
        background: rgba(0, 212, 255, 0.06) !important;
        border-color: rgba(0,212,255,0.15) !important;
        box-shadow: 0 0 12px rgba(0,212,255,0.05) !important;
    }
    div[data-testid="stSidebar"] div[role="radiogroup"] > label[data-checked="true"],
    div[data-testid="stSidebar"] div[role="radiogroup"] > label:has(input:checked) {
        background: rgba(0, 212, 255, 0.1) !important;
        border: 1px solid rgba(0,212,255,0.35) !important;
        box-shadow: 0 0 20px rgba(0,212,255,0.1), inset 0 1px 0 rgba(0,212,255,0.15) !important;
    }
    div[data-testid="stSidebar"] div[role="radiogroup"] > label[data-checked="true"] span,
    div[data-testid="stSidebar"] div[role="radiogroup"] > label:has(input:checked) span,
    div[data-testid="stSidebar"] div[role="radiogroup"] > label[data-checked="true"] p,
    div[data-testid="stSidebar"] div[role="radiogroup"] > label:has(input:checked) p {
        color: #00d4ff !important;
        font-weight: 600 !important;
        text-shadow: 0 0 8px rgba(0,212,255,0.3) !important;
    }
    div[data-testid="stSidebar"] div[role="radiogroup"] span,
    div[data-testid="stSidebar"] div[role="radiogroup"] p {
        font-size: 13px !important;
        font-weight: 500 !important;
        color: #8892a4 !important;
    }

    /* ═══ STREAMLIT HEADER — TRANSPARENT ═══ */
    header[data-testid="stHeader"] {
        background: rgba(8, 9, 13, 0.8) !important;
        backdrop-filter: blur(20px) !important;
        -webkit-backdrop-filter: blur(20px) !important;
        border-bottom: 1px solid rgba(255,255,255,0.04) !important;
        color: #e2e8f0 !important;
    }
    header[data-testid="stHeader"] svg {
        fill: #e2e8f0 !important;
    }
    header[data-testid="stHeader"] button {
        color: #e2e8f0 !important;
        border-color: rgba(255,255,255,0.1) !important;
    }

    /* ═══ PORTAL HEADER BAR — FROSTED DARK GLASS ═══ */
    .portal-header-bar {
        background: rgba(17, 19, 24, 0.85) !important;
        border: 1px solid rgba(255,255,255,0.06) !important;
        border-radius: 14px !important;
        padding: 14px 20px !important;
        margin-bottom: 22px !important;
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        flex-wrap: wrap !important;
        gap: 14px !important;
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255,255,255,0.03) !important;
        backdrop-filter: blur(20px) saturate(180%) !important;
        -webkit-backdrop-filter: blur(20px) saturate(180%) !important;
        position: relative !important;
        overflow: hidden !important;
    }
    .portal-header-bar::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        height: 100%;
        width: 3px;
        background: linear-gradient(180deg, #00d4ff, #a855f7);
        box-shadow: 0 0 12px rgba(0,212,255,0.4);
    }
    .portal-header-brand {
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .portal-logo-glow {
        width: 42px;
        height: 42px;
        min-width: 42px;
        border-radius: 10px;
        background: linear-gradient(135deg, #00d4ff, #0099cc) !important;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #08090d !important;
        font-size: 19px;
        box-shadow: 0 0 20px rgba(0,212,255,0.25), 0 4px 12px rgba(0,0,0,0.3) !important;
    }
    .portal-title-text {
        font-size: 17px;
        font-weight: 800;
        letter-spacing: -0.025em;
        color: #f1f5f9 !important;
        line-height: 1.2;
    }
    .portal-breadcrumb {
        font-size: 12px;
        color: #64748b !important;
        font-weight: 500;
        margin-top: 2px;
    }
    .portal-breadcrumb-active {
        color: #00d4ff !important;
        font-weight: 700;
    }
    .portal-status-group {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .portal-status-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 11.5px;
        font-weight: 600;
        background: rgba(17,19,24,0.8);
        border: 1px solid rgba(255,255,255,0.08);
        color: #94a3b8;
        backdrop-filter: blur(8px);
    }
    .portal-status-chip.online {
        background: rgba(0, 255, 136, 0.08) !important;
        border-color: rgba(0, 255, 136, 0.25) !important;
        color: #00ff88 !important;
        box-shadow: 0 0 10px rgba(0,255,136,0.05) !important;
    }
    .portal-status-chip.source {
        background: rgba(0, 212, 255, 0.08) !important;
        border-color: rgba(0, 212, 255, 0.2) !important;
        color: #00d4ff !important;
    }
    .portal-status-chip.feed {
        background: rgba(168, 85, 247, 0.08) !important;
        border-color: rgba(168, 85, 247, 0.2) !important;
        color: #a855f7 !important;
    }
    .status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #00ff88;
        box-shadow: 0 0 8px #00ff88, 0 0 16px rgba(0,255,136,0.3);
        animation: pulseDot 2s infinite ease-in-out;
    }
    @keyframes pulseDot {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.4); opacity: 0.6; }
    }

    /* ═══ TAB HERO HEADER — NEON ACCENTED ═══ */
    .tab-hero {
        background: rgba(17, 19, 24, 0.85) !important;
        border: 1px solid rgba(255,255,255,0.06) !important;
        border-radius: 14px !important;
        padding: 16px 20px !important;
        margin-bottom: 20px !important;
        display: flex !important;
        align-items: center !important;
        gap: 16px !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3), inset 0 1px 0 rgba(255,255,255,0.03) !important;
        backdrop-filter: blur(16px) !important;
        position: relative !important;
        overflow: hidden !important;
    }
    .tab-hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 3px;
        height: 100%;
        background: linear-gradient(180deg, #00d4ff, #a855f7);
        box-shadow: 0 0 12px rgba(0,212,255,0.4);
    }
    .tab-hero-icon {
        width: 44px;
        height: 44px;
        min-width: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }
    .tab-hero-content {
        flex: 1;
    }
    .tab-hero-title {
        font-size: 19px;
        font-weight: 700;
        color: #f1f5f9 !important;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
        letter-spacing: -0.025em;
    }
    .tab-hero-subtitle {
        font-size: 13px;
        color: #64748b !important;
        margin: 3px 0 0 0;
        line-height: 1.4;
    }
    .tab-hero-badge {
        font-size: 11px;
        font-weight: 700;
        padding: 2px 9px;
        border-radius: 20px;
        letter-spacing: 0.3px;
    }

    /* ═══ BUTTONS — NEON PRIMARY ═══ */
    .stButton > button[kind="primary"],
    .stButton > button[data-testid="baseButton-primary"] {
        background: linear-gradient(135deg, #00d4ff, #0099cc) !important;
        color: #08090d !important;
        font-weight: 700 !important;
        border: 1px solid rgba(0,212,255,0.3) !important;
        border-radius: 8px !important;
        padding: 9px 20px !important;
        font-size: 13.5px !important;
        letter-spacing: -0.01em !important;
        box-shadow: 0 0 16px rgba(0,212,255,0.15), 0 2px 8px rgba(0,0,0,0.3) !important;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }
    .stButton > button[kind="primary"]:hover,
    .stButton > button[data-testid="baseButton-primary"]:hover {
        background: linear-gradient(135deg, #33dfff, #00b8e6) !important;
        box-shadow: 0 0 24px rgba(0,212,255,0.25), 0 4px 16px rgba(0,0,0,0.4) !important;
        color: #08090d !important;
        transform: translateY(-1px) !important;
    }
    .stButton > button[kind="secondary"],
    .stButton > button:not([kind="primary"]) {
        background: rgba(17,19,24,0.7) !important;
        border: 1px solid rgba(255,255,255,0.1) !important;
        color: #e2e8f0 !important;
        border-radius: 8px !important;
        padding: 8px 18px !important;
        font-weight: 500 !important;
        font-size: 13.5px !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2) !important;
        transition: all 0.2s ease !important;
        backdrop-filter: blur(8px) !important;
    }
    .stButton > button[kind="secondary"]:hover,
    .stButton > button:not([kind="primary"]):hover {
        border-color: rgba(0,212,255,0.2) !important;
        color: #00d4ff !important;
        background: rgba(0,212,255,0.06) !important;
        box-shadow: 0 0 16px rgba(0,212,255,0.06) !important;
        transform: translateY(-1px) !important;
    }

    /* ═══ FORM INPUTS — DARK GLASS ═══ */
    div[data-baseweb="select"] > div,
    div[data-baseweb="input"] > div {
        background-color: rgba(17,19,24,0.8) !important;
        border: 1px solid rgba(255,255,255,0.08) !important;
        border-radius: 8px !important;
        color: #e2e8f0 !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2) !important;
        transition: border-color 0.2s ease, box-shadow 0.2s ease !important;
    }
    div[data-baseweb="select"] > div:hover,
    div[data-baseweb="input"] > div:hover {
        border-color: rgba(0,212,255,0.15) !important;
    }
    div[data-baseweb="select"] > div:focus-within,
    div[data-baseweb="input"] > div:focus-within {
        border-color: rgba(0,212,255,0.4) !important;
        box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.08), 0 0 16px rgba(0,212,255,0.06) !important;
    }
    div[data-baseweb="select"] span {
        color: #e2e8f0 !important;
    }
    input {
        color: #e2e8f0 !important;
    }
    div[data-baseweb="popover"], div[data-baseweb="menu"], ul[role="listbox"] {
        background: rgba(17, 19, 24, 0.95) !important;
        border: 1px solid rgba(255,255,255,0.08) !important;
        border-radius: 10px !important;
        box-shadow: 0 16px 40px rgba(0, 0, 0, 0.5), 0 0 1px rgba(255,255,255,0.1) !important;
        backdrop-filter: blur(20px) !important;
    }
    ul[role="listbox"] li {
        color: #e2e8f0 !important;
        background: transparent !important;
    }
    ul[role="listbox"] li:hover, ul[role="listbox"] li[aria-selected="true"] {
        background: rgba(0, 212, 255, 0.08) !important;
        color: #00d4ff !important;
    }

    /* ═══ PANELS & CARDS — FROSTED DARK GLASS ═══ */
    .glass-panel {
        background: rgba(17, 19, 24, 0.85) !important;
        border: 1px solid rgba(255,255,255,0.06) !important;
        border-radius: 14px !important;
        padding: 18px 22px !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3), inset 0 1px 0 rgba(255,255,255,0.03) !important;
        margin-bottom: 20px !important;
        backdrop-filter: blur(16px) !important;
    }
    .metric-box {
        background: rgba(17, 19, 24, 0.85) !important;
        border: 1px solid rgba(255,255,255,0.06) !important;
        border-radius: 12px !important;
        padding: 16px 20px !important;
        color: #e2e8f0 !important;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25) !important;
        backdrop-filter: blur(12px) !important;
    }
    .metric-value {
        font-size: 26px !important;
        font-weight: 800 !important;
        color: #f1f5f9 !important;
        letter-spacing: -0.03em !important;
    }
    .metric-label {
        font-size: 11.5px !important;
        color: #64748b !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        font-weight: 600 !important;
    }

    /* ═══ EXECUTIVE KPI CARDS — GLOWING BORDERS ═══ */
    .portal-kpi-card {
        background: rgba(17, 19, 24, 0.85) !important;
        border: 1px solid rgba(255,255,255,0.06) !important;
        border-radius: 12px !important;
        padding: 18px 20px !important;
        position: relative !important;
        overflow: hidden !important;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25) !important;
        backdrop-filter: blur(12px) !important;
    }
    .portal-kpi-card:hover {
        transform: translateY(-3px) !important;
        border-color: rgba(0,212,255,0.15) !important;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.35), 0 0 20px rgba(0,212,255,0.05) !important;
    }

    /* ═══ QUICK LAUNCH ACTION TILES ═══ */
    .action-tile {
        background: rgba(17, 19, 24, 0.8) !important;
        border: 1px solid rgba(255,255,255,0.06) !important;
        border-radius: 12px !important;
        padding: 20px !important;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
        cursor: pointer !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: space-between !important;
        height: 100% !important;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25) !important;
        backdrop-filter: blur(12px) !important;
    }
    .action-tile:hover {
        border-color: rgba(0,212,255,0.2) !important;
        transform: translateY(-3px) !important;
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.4), 0 0 20px rgba(0,212,255,0.06) !important;
    }

    /* Portal Card */
    .portal-card {
        background: rgba(17, 19, 24, 0.85) !important;
        border: 1px solid rgba(255,255,255,0.06) !important;
        border-radius: 12px !important;
        padding: 14px !important;
        margin-bottom: 12px !important;
        transition: all 0.25s ease !important;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25) !important;
        backdrop-filter: blur(12px) !important;
    }
    .portal-card:hover {
        border-color: rgba(0,212,255,0.15) !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35), 0 0 16px rgba(0,212,255,0.05) !important;
    }

    /* ═══ PROPERTY CARD — DARK GLASS WITH GLOW ═══ */
    .prop-card {
        background-color: rgba(17, 19, 24, 0.85) !important;
        border: 1px solid rgba(255,255,255,0.06) !important;
        border-radius: 14px !important;
        padding: 12px !important;
        margin-bottom: 14px !important;
        transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.25s ease, box-shadow 0.25s ease !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: space-between !important;
        overflow: hidden !important;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3) !important;
        backdrop-filter: blur(12px) !important;
    }
    .prop-card:hover {
        border-color: rgba(0,212,255,0.15) !important;
        transform: translateY(-4px) !important;
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.4), 0 0 24px rgba(0,212,255,0.06) !important;
    }
    .prop-thumb-box {
        position: relative;
        width: 100%;
        height: 180px;
        border-radius: 10px;
        overflow: hidden;
        background-color: rgba(25,28,36,0.8);
        margin-bottom: 10px;
        border: 1px solid rgba(255,255,255,0.04);
    }
    .prop-thumb {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .prop-card:hover .prop-thumb {
        transform: scale(1.05);
    }
    .prop-badge-overlay {
        position: absolute;
        top: 8px;
        left: 8px;
        background-color: rgba(8, 9, 13, 0.85);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        color: #e2e8f0;
        border: 1px solid rgba(255,255,255,0.1);
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 5px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
    }
    .prop-urgent-overlay {
        position: absolute;
        top: 8px;
        right: 8px;
        background: linear-gradient(135deg, #ff006e, #ff3388);
        color: white;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 800;
        box-shadow: 0 0 12px rgba(255, 0, 110, 0.35), 0 2px 8px rgba(0,0,0,0.3);
        display: flex;
        align-items: center;
        gap: 4px;
        letter-spacing: 0.3px;
    }
    .prop-title {
        font-size: 14px;
        font-weight: 600;
        color: #e2e8f0 !important;
        margin-bottom: 6px;
        line-height: 1.4;
        min-height: 40px;
    }
    .prop-price {
        font-size: 18px;
        font-weight: 800;
        letter-spacing: -0.025em;
        color: #00d4ff;
    }
    .prop-meta {
        font-size: 12.5px;
        color: #64748b !important;
        margin-top: 4px;
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    /* ═══ GRID TABLE — DARK TERMINAL ═══ */
    .grid-table-container {
        width: 100%;
        overflow-x: auto;
        border: 1px solid rgba(255,255,255,0.06) !important;
        border-radius: 12px !important;
        background: rgba(17, 19, 24, 0.85) !important;
        margin-top: 10px;
        margin-bottom: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;
        backdrop-filter: blur(12px) !important;
    }
    .grid-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 13px;
        color: #94a3b8;
    }
    .grid-table th {
        background: rgba(8, 9, 13, 0.9) !important;
        color: #64748b !important;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.5px;
        padding: 12px 14px;
        border-bottom: 1px solid rgba(255,255,255,0.06) !important;
        white-space: nowrap;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    .grid-table td {
        padding: 11px 14px;
        border-bottom: 1px solid rgba(255,255,255,0.03) !important;
        vertical-align: middle;
        color: #e2e8f0;
    }
    .grid-table tr:hover td {
        background-color: rgba(0, 212, 255, 0.03) !important;
    }
    .grid-thumb {
        width: 48px;
        height: 48px;
        min-width: 48px;
        border-radius: 8px;
        object-fit: cover;
        background-color: rgba(25,28,36,0.8);
        border: 1px solid rgba(255,255,255,0.06);
    }
    .grid-title-box {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 260px;
        max-width: 380px;
    }
    .grid-title-text {
        font-weight: 600;
        color: #e2e8f0 !important;
        line-height: 1.35;
        font-size: 13px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .grid-link-btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: rgba(0, 212, 255, 0.08) !important;
        color: #00d4ff !important;
        border: 1px solid rgba(0,212,255,0.2) !important;
        padding: 5px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none !important;
        transition: all 0.2s ease;
        white-space: nowrap;
    }
    .grid-link-btn:hover {
        background: rgba(0, 212, 255, 0.15) !important;
        border-color: rgba(0,212,255,0.35) !important;
        box-shadow: 0 0 12px rgba(0,212,255,0.1) !important;
        transform: translateY(-1px);
    }

    /* ═══ BADGES — NEON VARIANTS ═══ */
    .badge-deal {
        background: rgba(0, 255, 136, 0.08) !important;
        color: #00ff88 !important;
        border: 1px solid rgba(0,255,136,0.2) !important;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        text-shadow: 0 0 6px rgba(0,255,136,0.2);
    }
    .badge-urgent {
        background: rgba(255, 0, 110, 0.08) !important;
        color: #ff006e !important;
        border: 1px solid rgba(255,0,110,0.2) !important;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        text-shadow: 0 0 6px rgba(255,0,110,0.2);
    }

    /* ═══ STREAMLIT TABS — NEON UNDERLINE ═══ */
    div[data-baseweb="tab-list"] {
        border-bottom: 1px solid rgba(255,255,255,0.06) !important;
        gap: 8px !important;
    }
    button[data-baseweb="tab"] {
        color: #64748b !important;
        font-weight: 600 !important;
        font-size: 13.5px !important;
        padding: 10px 16px !important;
        border-bottom: 2px solid transparent !important;
        transition: all 0.2s ease !important;
    }
    button[data-baseweb="tab"]:hover {
        color: #e2e8f0 !important;
    }
    button[data-baseweb="tab"][aria-selected="true"] {
        color: #00d4ff !important;
        border-bottom: 2px solid #00d4ff !important;
        text-shadow: 0 0 8px rgba(0,212,255,0.3) !important;
    }

    /* ═══ EXPANDERS — DARK GLASS ═══ */
    div[data-testid="stExpander"] {
        background: rgba(17, 19, 24, 0.85) !important;
        border: 1px solid rgba(255,255,255,0.06) !important;
        border-radius: 10px !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2) !important;
    }
    div[data-testid="stExpander"] summary {
        color: #e2e8f0 !important;
        font-weight: 600 !important;
    }

    /* ═══ DATAFRAMES ═══ */
    [data-testid="stDataFrame"] {
        background: rgba(17, 19, 24, 0.85) !important;
        border: 1px solid rgba(255,255,255,0.06) !important;
        border-radius: 10px !important;
    }

    /* ═══ PRESERVE ICON FONTS ═══ */
    [data-testid="stIconMaterial"], .material-symbols-rounded, .material-symbols-outlined, .material-icons, [class*="material-symbols"] {
        font-family: 'Material Symbols Rounded', 'Material Symbols Outlined', 'Material Icons' !important;
    }
    i, [class*="fa-"], .fa, .fas, .far, .fab, .fa-solid, .fa-regular, .fa-brands {
        font-family: "Font Awesome 6 Free", "Font Awesome 6 Brands", FontAwesome !important;
    }

    /* ═══ SCROLLBAR — DARK NEON ═══ */
    ::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    ::-webkit-scrollbar-track {
        background: transparent;
    }
    ::-webkit-scrollbar-thumb {
        background: rgba(255,255,255,0.08);
        border-radius: 3px;
    }
    ::-webkit-scrollbar-thumb:hover {
        background: rgba(0,212,255,0.2);
    }

    /* ═══ PROGRESS BAR & SPINNER ═══ */
    .stProgress > div > div > div {
        background: linear-gradient(90deg, #00d4ff, #a855f7) !important;
        box-shadow: 0 0 12px rgba(0,212,255,0.3) !important;
    }

    /* ═══ METRIC OVERRIDES ═══ */
    [data-testid="stMetricValue"] {
        color: #f1f5f9 !important;
    }
    [data-testid="stMetricLabel"] {
        color: #64748b !important;
    }
    [data-testid="stMetricDelta"] svg {
        fill: #00ff88 !important;
    }
</style>
""", unsafe_allow_html=True)

# Curated High-Resolution Real Estate Fallback Thumbnails by Category
FALLBACK_THUMBNAILS = {
    "Land": "https://images.unsplash.com/photo-1500382017468-9049fed747ef?w=700&auto=format&fit=crop&q=80",
    "Condo": "https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?w=700&auto=format&fit=crop&q=80",
    "Villa": "https://images.unsplash.com/photo-1580587771525-78b9dba3b914?w=700&auto=format&fit=crop&q=80",
    "House": "https://images.unsplash.com/photo-1568605117036-5fe5e7bab0b7?w=700&auto=format&fit=crop&q=80",
    "Shophouse": "https://images.unsplash.com/photo-1577495508048-b635879837f1?w=700&auto=format&fit=crop&q=80",
    "Apartment": "https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=700&auto=format&fit=crop&q=80",
    "Commercial": "https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=700&auto=format&fit=crop&q=80",
    "Warehouse": "https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?w=700&auto=format&fit=crop&q=80",
    "Borey": "https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=700&auto=format&fit=crop&q=80",
    "Other": "https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=700&auto=format&fit=crop&q=80",
}

PROPERTY_TYPE_ICONS = {
    "Land": "fa-solid fa-layer-group",
    "Condo": "fa-solid fa-building",
    "Villa": "fa-solid fa-house-chimney-window",
    "House": "fa-solid fa-house",
    "Shophouse": "fa-solid fa-store",
    "Apartment": "fa-solid fa-door-open",
    "Commercial": "fa-solid fa-briefcase",
    "Warehouse": "fa-solid fa-warehouse",
    "Borey": "fa-solid fa-tree-city",
    "Other": "fa-solid fa-location-arrow"
}

def render_html(html_str: str):
    cleaned = "\n".join(line.strip() for line in html_str.splitlines() if line.strip())
    st.markdown(cleaned, unsafe_allow_html=True)

def render_tab_hero(icon_class: str, title: str, subtitle: str, badge_text: str = None, color: str = "#00d4ff", bg_gradient: str = "rgba(0,212,255,0.1)"):
    badge_html = f"<span class='tab-hero-badge' style='color:{color}; border:1px solid {color}33; background:{bg_gradient};'>{badge_text}</span>" if badge_text else ""
    render_html(f"""
    <div class="tab-hero">
        <div class="tab-hero-icon" style="background:{bg_gradient}; color:{color}; border: 1px solid {color}33;">
            <i class="{icon_class}"></i>
        </div>
        <div class="tab-hero-content">
            <div class="tab-hero-title">
                {title}
                {badge_html}
            </div>
            <div class="tab-hero-subtitle">{subtitle}</div>
        </div>
    </div>
    """)

def get_property_thumbnail(raw_url: Any, ptype: str = "Other") -> tuple[str, str]:
    """Returns (primary_thumbnail_url, fallback_thumbnail_url)."""
    fallback = FALLBACK_THUMBNAILS.get(ptype, FALLBACK_THUMBNAILS["Other"])
    if raw_url and isinstance(raw_url, str) and raw_url.strip().startswith("http"):
        return raw_url.strip(), fallback
    return fallback, fallback

# Initialize database
init_db()

# State Management for Portal Workspaces
WORKSPACES = [
    "🌐 Command Center",
    "🕷️ Scraping Operations",
    "📋 MLS Property Explorer",
    "⚖️ CMA Valuation Studio",
    "💎 Deal Finder & Screener",
    "📐 Land Valuation Estimator",
    "📊 Market Intelligence Hub",
    "⚙️ Database & System Console"
]

if "portal_workspace" not in st.session_state:
    st.session_state["portal_workspace"] = "🌐 Command Center"
if "global_search" not in st.session_state:
    st.session_state["global_search"] = ""

# Fetch summary stats
stats = get_summary_stats()
tot_props = max(stats.get('total_properties', 0), 1)

# ==============================================================================
# SIDEBAR: BRAND, NAVIGATION & TELEMETRY
# ==============================================================================
st.sidebar.markdown("""
<div class="sidebar-brand">
    <div class="sidebar-brand-icon" style="width:36px; height:36px; min-width:36px; border-radius:9px; background:linear-gradient(135deg, #00d4ff, #0099cc); display:flex; align-items:center; justify-content:center; color:#08090d; font-size:16px; box-shadow: 0 0 16px rgba(0,212,255,0.2);">
        <i class="fa-solid fa-city"></i>
    </div>
    <div>
        <div style="font-weight: 800; font-size: 14.5px; color: #f1f5f9; letter-spacing: -0.02em; line-height: 1.2;">CAMBODIA PROPTECH</div>
        <div style="font-size: 11px; color: #00d4ff; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; text-shadow: 0 0 8px rgba(0,212,255,0.2);">Enterprise MLS Portal</div>
    </div>
</div>
""", unsafe_allow_html=True)

# Portal Workspace Navigation Menu in Sidebar
st.sidebar.markdown("<div style='font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.8px; margin:0 0 8px 0;'><i class='fa-solid fa-compass' style='margin-right:6px; color:#00d4ff;'></i>Portal Workspaces</div>", unsafe_allow_html=True)

# Find current index
current_ws = st.session_state["portal_workspace"]
default_idx = WORKSPACES.index(current_ws) if current_ws in WORKSPACES else 0

selected_ws = st.sidebar.radio(
    "Select Workspace",
    WORKSPACES,
    index=default_idx,
    label_visibility="collapsed"
)

if selected_ws != st.session_state["portal_workspace"]:
    st.session_state["portal_workspace"] = selected_ws
    st.rerun()

st.sidebar.markdown("<hr style='border:none; border-top:1px solid rgba(255,255,255,0.06); margin:16px 0 12px 0;'/>", unsafe_allow_html=True)
st.sidebar.markdown("<div style='font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.8px; margin-bottom:10px;'><i class='fa-solid fa-chart-line' style='margin-right:6px; color:#00d4ff;'></i>Market Telemetry</div>", unsafe_allow_html=True)

# 2 Top Highlights (Total Listings & Portals)
col_s1, col_s2 = st.sidebar.columns(2)
with col_s1:
    st.markdown(f"""
    <div class="sidebar-stat-card" style="flex-direction:column; align-items:flex-start; gap:4px; padding:12px;">
        <div style="font-size:10px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px;">Total Listings</div>
        <div style="font-size:18px; font-weight:800; color:#f1f5f9; letter-spacing:-0.03em;">{stats['total_properties']:,}</div>
        <div style="font-size:10.5px; color:#00d4ff; font-weight:600;"><i class="fa-solid fa-database" style="margin-right:3px;"></i> Live in DB</div>
    </div>
    """, unsafe_allow_html=True)
with col_s2:
    st.markdown(f"""
    <div class="sidebar-stat-card" style="flex-direction:column; align-items:flex-start; gap:4px; padding:12px;">
        <div style="font-size:10px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px;">Portals</div>
        <div style="font-size:18px; font-weight:800; color:#f1f5f9; letter-spacing:-0.03em;">{stats['sources_count']} / 5</div>
        <div style="font-size:10.5px; color:#00ff88; font-weight:600;"><i class="fa-solid fa-signal" style="margin-right:3px;"></i> Operational</div>
    </div>
    """, unsafe_allow_html=True)

# For Sale Sector Card
sale_cnt = stats.get('total_sale', 0)
avg_sale_p = stats.get('avg_sale_price', 0)
avg_sale_sqm = stats.get('avg_sale_pp_sqm', 0)
st.sidebar.markdown(f"""
<div class="sidebar-stat-card" style="border-left: 3px solid #00ff88; flex-direction:column; align-items:flex-start; gap:4px; padding:12px; margin-top:2px;">
    <div style="display:flex; justify-content:space-between; width:100%; align-items:center;">
        <span style="font-size:11px; font-weight:700; color:#00ff88; text-transform:uppercase; letter-spacing:0.5px;"><i class="fa-solid fa-tag" style="margin-right:4px;"></i> For Sale Market</span>
        <span style="background:rgba(0,255,136,0.08); color:#00ff88; font-weight:700; font-size:11px; padding:2px 7px; border-radius:6px; border:1px solid rgba(0,255,136,0.2);">{sale_cnt:,} units</span>
    </div>
    <div style="font-size:16px; font-weight:800; color:#f1f5f9; margin-top:2px;">${avg_sale_p:,.0f} <span style="font-size:11px; color:#64748b; font-weight:500;">avg price</span></div>
    <div style="font-size:11.5px; color:#00ff88; font-weight:700;">${avg_sale_sqm:,.0f} / m² <span style="font-size:10.5px; color:#64748b; font-weight:500;">rate benchmark</span></div>
</div>
""", unsafe_allow_html=True)

# Rental Market Card
rent_cnt = stats.get('total_rent', 0)
avg_rent_p = stats.get('avg_rent_price', 0)
avg_rent_sqm = stats.get('avg_rent_pp_sqm', 0)
st.sidebar.markdown(f"""
<div class="sidebar-stat-card" style="border-left: 3px solid #a855f7; flex-direction:column; align-items:flex-start; gap:4px; padding:12px;">
    <div style="display:flex; justify-content:space-between; width:100%; align-items:center;">
        <span style="font-size:11px; font-weight:700; color:#a855f7; text-transform:uppercase; letter-spacing:0.5px;"><i class="fa-solid fa-key" style="margin-right:4px;"></i> Rental Market</span>
        <span style="background:rgba(168,85,247,0.08); color:#a855f7; font-weight:700; font-size:11px; padding:2px 7px; border-radius:6px; border:1px solid rgba(168,85,247,0.2);">{rent_cnt:,} units</span>
    </div>
    <div style="font-size:16px; font-weight:800; color:#f1f5f9; margin-top:2px;">${avg_rent_p:,.0f} / mo <span style="font-size:11px; color:#64748b; font-weight:500;">avg rent</span></div>
    <div style="font-size:11.5px; color:#a855f7; font-weight:700;">${avg_rent_sqm:,.2f} / m²/mo <span style="font-size:10.5px; color:#64748b; font-weight:500;">rental rate</span></div>
</div>
""", unsafe_allow_html=True)

# Portal Coverage with percentage meter bars
st.sidebar.markdown("<div style='font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.8px; margin:16px 0 10px 0;'><i class='fa-solid fa-network-wired' style='margin-right:6px; color:#00d4ff;'></i>Portal Database Share</div>", unsafe_allow_html=True)
for src, count in sorted(stats["by_source"].items(), key=lambda x: x[1], reverse=True):
    src_info = WEBSITES.get(src, {"name": src.upper(), "color": "#2563eb", "icon": "fa-solid fa-globe"})
    site_color = src_info.get("color", "#2563eb")
    site_icon = src_info.get("icon", "fa-solid fa-globe")
    pct = (count / tot_props) * 100.0
    st.sidebar.markdown(f"""
    <div class="portal-stat-pill">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <div style="display:flex; align-items:center; gap:8px; color:#f1f5f9; font-weight:600; font-size:12.5px;">
                <i class="{site_icon}" style="color:{site_color}; font-size:13px; width:14px; text-align:center;"></i>
                <span>{src_info['name']}</span>
            </div>
            <span style="font-size:12px; font-weight:800; color:#f1f5f9;">{count:,} <span style="font-size:10px; color:#64748b; font-weight:500;">({pct:.1f}%)</span></span>
        </div>
        <div style="width:100%; height:4px; background:rgba(17,19,24,0.7); border-radius:10px; overflow:hidden;">
            <div style="width:{min(pct, 100):.1f}%; height:100%; background:{site_color}; border-radius:10px;"></div>
        </div>
    </div>
    """, unsafe_allow_html=True)

st.sidebar.markdown(f"""
<div style="margin-top:16px; padding:10px; border-radius:8px; background:rgba(17,19,24,0.5); border:1px solid #e2e8f0; font-size:11px; color:#64748b; text-align:center;">
    <i class="fa-solid fa-shield-halved" style="color:#00ff88; margin-right:4px;"></i> Enterprise Portal v2.5
</div>
""", unsafe_allow_html=True)


# ==============================================================================
# PERSISTENT ENTERPRISE PORTAL HEADER BAR
# ==============================================================================
active_workspace = st.session_state["portal_workspace"]

render_html(f"""
<div class="portal-header-bar">
    <div class="portal-header-brand">
        <div class="portal-logo-glow">
            <i class="fa-solid fa-city"></i>
        </div>
        <div>
            <div class="portal-title-text">CAMBODIA PROPTECH MLS INTELLIGENCE PORTAL</div>
            <div class="portal-breadcrumb">
                Enterprise MLS / <span class="portal-breadcrumb-active">{active_workspace}</span>
            </div>
        </div>
    </div>
    <div class="portal-status-group">
        <div class="portal-status-chip online">
            <span class="status-dot"></span>
            <span>Live MLS: <b>{stats['total_properties']:,}</b> Properties</span>
        </div>
        <div class="portal-status-chip source">
            <i class="fa-solid fa-network-wired"></i>
            <span><b>{stats['sources_count']}/5</b> Sources Connected</span>
        </div>
        <div class="portal-status-chip feed">
            <i class="fa-solid fa-bolt"></i>
            <span>Real-time Valuation</span>
        </div>
    </div>
</div>
""")


# ==============================================================================
# WORKSPACE 1: 🌐 COMMAND CENTER (EXECUTIVE DASHBOARD)
# ==============================================================================
def render_command_center():
    render_tab_hero(
        icon_class="fa-solid fa-gauge-high",
        title="Executive Command Center & Telemetry",
        subtitle="Centralized intelligence terminal monitoring Cambodia's top 5 property portals, market rates, and high-margin deals.",
        badge_text="Real-time Portal Engine",
        color="#2563eb",
        bg_gradient="#eff6ff"
    )

    # 4 Executive KPI Cards
    k1, k2, k3, k4 = st.columns(4)
    with k1:
        render_html(f"""
        <div class="portal-kpi-card" style="border-left: 3px solid #00d4ff;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                <span class="metric-label">Ingested MLS Listings</span>
                <i class="fa-solid fa-database" style="color:#00d4ff; font-size:16px;"></i>
            </div>
            <div class="metric-value" style="color:#f1f5f9;">{stats['total_properties']:,}</div>
            <div style="font-size:12px; color:#00d4ff; margin-top:6px; font-weight:600;">
                <i class="fa-solid fa-circle-check" style="margin-right:4px;"></i>5 Aggregated Portals
            </div>
        </div>
        """)
    with k2:
        render_html(f"""
        <div class="portal-kpi-card" style="border-left: 3px solid #00ff88;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                <span class="metric-label">For Sale Median Benchmark</span>
                <i class="fa-solid fa-tag" style="color:#00ff88; font-size:16px;"></i>
            </div>
            <div class="metric-value" style="color:#00ff88;">${stats.get('avg_sale_pp_sqm', 0):,.0f} <span style="font-size:13px; color:#64748b; font-weight:500;">/ m²</span></div>
            <div style="font-size:12px; color:#64748b; margin-top:6px;">
                Avg: <b style="color:#f1f5f9;">${stats.get('avg_sale_price', 0):,.0f}</b> ({stats.get('total_sale', 0):,} units)
            </div>
        </div>
        """)
    with k3:
        render_html(f"""
        <div class="portal-kpi-card" style="border-left: 3px solid #a855f7;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                <span class="metric-label">Rental Yield Benchmark</span>
                <i class="fa-solid fa-key" style="color:#a855f7; font-size:16px;"></i>
            </div>
            <div class="metric-value" style="color:#a855f7;">${stats.get('avg_rent_pp_sqm', 0):,.2f} <span style="font-size:13px; color:#64748b; font-weight:500;">/ m²/mo</span></div>
            <div style="font-size:12px; color:#64748b; margin-top:6px;">
                Avg: <b style="color:#f1f5f9;">${stats.get('avg_rent_price', 0):,.0f}/mo</b> ({stats.get('total_rent', 0):,} units)
            </div>
        </div>
        """)
    with k4:
        render_html(f"""
        <div class="portal-kpi-card" style="border-left: 3px solid #00ff88;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                <span class="metric-label">Engine Health Status</span>
                <i class="fa-solid fa-shield-heart" style="color:#00ff88; font-size:16px;"></i>
            </div>
            <div class="metric-value" style="color:#00ff88; font-size:22px;">100% OPERATIONAL</div>
            <div style="font-size:12px; color:#00ff88; margin-top:6px; font-weight:600;">
                <i class="fa-solid fa-bolt" style="margin-right:4px;"></i>Direct REST & Playwright Ready
            </div>
        </div>
        """)

    st.markdown("<div style='margin-top:24px;'></div>", unsafe_allow_html=True)

    # Quick Launch Operations Deck
    st.markdown("<div style='font-size:14px; font-weight:700; color:#f1f5f9; margin-bottom:12px;'><i class='fa-solid fa-rocket' style='color:#00d4ff; margin-right:8px;'></i>Quick-Launch Operations Studio</div>", unsafe_allow_html=True)
    
    act1, act2, act3, act4 = st.columns(4)
    with act1:
        st.markdown("""
        <div class="action-tile">
            <div>
                <div style="width:38px; height:38px; border-radius:8px; background:rgba(0,212,255,0.08); color:#00d4ff; display:flex; align-items:center; justify-content:center; font-size:17px; margin-bottom:12px;">
                    <i class="fa-solid fa-network-wired"></i>
                </div>
                <div style="font-weight:700; font-size:14.5px; color:#f1f5f9; margin-bottom:4px;">Scraping Operations</div>
                <div style="font-size:12px; color:#64748b; line-height:1.4;">Harvest new listings across ARC, Harbor, C21, Khmer24 & Realestate.</div>
            </div>
        </div>
        """, unsafe_allow_html=True)
        if st.button("🚀 Launch Scraper", key="ql_scrape", use_container_width=True):
            st.session_state["portal_workspace"] = "🕷️ Scraping Operations"
            st.rerun()

    with act2:
        st.markdown("""
        <div class="action-tile">
            <div>
                <div style="width:38px; height:38px; border-radius:8px; background:rgba(0,255,136,0.08); color:#00ff88; display:flex; align-items:center; justify-content:center; font-size:17px; margin-bottom:12px;">
                    <i class="fa-solid fa-magnifying-glass-location"></i>
                </div>
                <div style="font-weight:700; font-size:14.5px; color:#f1f5f9; margin-bottom:4px;">MLS Property Explorer</div>
                <div style="font-size:12px; color:#64748b; line-height:1.4;">Search 16,000+ listings with filters, responsive card grid, and Leaflet map.</div>
            </div>
        </div>
        """, unsafe_allow_html=True)
        if st.button("📋 Open Explorer", key="ql_explore", use_container_width=True):
            st.session_state["portal_workspace"] = "📋 MLS Property Explorer"
            st.rerun()

    with act3:
        st.markdown("""
        <div class="action-tile">
            <div>
                <div style="width:38px; height:38px; border-radius:8px; background:#eef2ff; color:#a855f7; display:flex; align-items:center; justify-content:center; font-size:17px; margin-bottom:12px;">
                    <i class="fa-solid fa-scale-balanced"></i>
                </div>
                <div style="font-weight:700; font-size:14.5px; color:#f1f5f9; margin-bottom:4px;">CMA Valuation Studio</div>
                <div style="font-size:12px; color:#64748b; line-height:1.4;">Automated comparative valuation model by Khan and landed size bracket.</div>
            </div>
        </div>
        """, unsafe_allow_html=True)
        if st.button("⚖️ Value Property", key="ql_cma", use_container_width=True):
            st.session_state["portal_workspace"] = "⚖️ CMA Valuation Studio"
            st.rerun()

    with act4:
        st.markdown("""
        <div class="action-tile">
            <div>
                <div style="width:38px; height:38px; border-radius:8px; background:#fdf2f8; color:#ff006e; display:flex; align-items:center; justify-content:center; font-size:17px; margin-bottom:12px;">
                    <i class="fa-solid fa-gem"></i>
                </div>
                <div style="font-weight:700; font-size:14.5px; color:#f1f5f9; margin-bottom:4px;">Deal Finder Screener</div>
                <div style="font-size:12px; color:#64748b; line-height:1.4;">Algorithmic detection of listings trading at 15%–40% below median $/m².</div>
            </div>
        </div>
        """, unsafe_allow_html=True)
        if st.button("💎 Scan Top Deals", key="ql_deals", use_container_width=True):
            st.session_state["portal_workspace"] = "💎 Deal Finder & Screener"
            st.rerun()

    st.markdown("<div style='margin-top:24px;'></div>", unsafe_allow_html=True)

    # Portal Live Connectivity & Health Matrix
    st.markdown("<div style='font-size:14px; font-weight:700; color:#f1f5f9; margin-bottom:12px;'><i class='fa-solid fa-satellite-dish' style='color:#00d4ff; margin-right:8px;'></i>Live Portal Synchronization & Status Matrix</div>", unsafe_allow_html=True)
    
    p_cols = st.columns(5)
    for idx, (s_key, s_data) in enumerate(WEBSITES.items()):
        cnt = stats["by_source"].get(s_key, 0)
        share_pct = (cnt / tot_props) * 100.0
        with p_cols[idx]:
            render_html(f"""
            <div class="portal-card" style="border-top: 3px solid {s_data.get('color', '#2563eb')}; padding:14px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                    <div style="display:flex; align-items:center; gap:6px;">
                        <i class="{s_data.get('icon', 'fa-solid fa-globe')}" style="color:{s_data.get('color', '#2563eb')};"></i>
                        <span style="font-weight:700; font-size:13px; color:#f1f5f9;">{s_data['name'].split('(')[0]}</span>
                    </div>
                    <span style="width:7px; height:7px; border-radius:50%; background:#10b981;"></span>
                </div>
                <div style="font-size:20px; font-weight:800; color:#f1f5f9; margin:4px 0;">{cnt:,}</div>
                <div style="font-size:11px; color:#64748b; display:flex; justify-content:space-between;">
                    <span>Share: <b>{share_pct:.1f}%</b></span>
                    <span style="color:#00d4ff; font-weight:600;">{s_data['speed']}</span>
                </div>
                <div style="margin-top:8px; padding-top:6px; border-top:1px solid #f1f5f9; font-size:10.5px; color:#94a3b8;">
                    Method: {s_data['method']}
                </div>
            </div>
            """)

    st.markdown("<div style='margin-top:24px;'></div>", unsafe_allow_html=True)

    # Recent High-Value Market Ingestions
    st.markdown("<div style='display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;'><div style='font-size:14px; font-weight:700; color:#f1f5f9;'><i class='fa-solid fa-clock-rotate-left' style='color:#00d4ff; margin-right:8px;'></i>Recent MLS Ingestion Stream (Live Database Feed)</div></div>", unsafe_allow_html=True)
    
    pulse_tab1, pulse_tab2 = st.tabs(["🏷️ Latest Properties For Sale", "🔑 Latest Rental Ingestions"])
    
    with pulse_tab1:
        recent_sales = get_recent_properties(limit=6, listing_type="Sale")
        if not recent_sales.empty:
            r_cols = st.columns(3)
            for idx, r_row in recent_sales.iterrows():
                with r_cols[idx % 3]:
                    ptype = r_row.get("property_type", "Other")
                    ptype_icon = PROPERTY_TYPE_ICONS.get(ptype, "fa-solid fa-building")
                    thumb_url, fallback_url = get_property_thumbnail(r_row.get("image_url"), ptype)
                    price_disp = f"${r_row.get('price_usd', 0):,.0f}" if pd.notnull(r_row.get('price_usd')) else "Contact"
                    rate_disp = f"${r_row.get('price_per_sqm', 0):,.0f}/m²" if pd.notnull(r_row.get('price_per_sqm')) and r_row.get('price_per_sqm') > 0 else ""
                    urg_tag = f"<div class='prop-urgent-overlay'><i class='fa-solid fa-fire'></i> {r_row['urgency_tag']}</div>" if r_row.get('urgency_tag') else ""
                    
                    render_html(f"""
                    <div class="prop-card">
                        <div class="prop-thumb-box" style="height:140px;">
                            <img src="{thumb_url}" onerror="this.onerror=null; this.src='{fallback_url}';" class="prop-thumb" alt="Thumbnail" />
                            <div class="prop-badge-overlay"><i class="{ptype_icon}"></i> {ptype}</div>
                            {urg_tag}
                        </div>
                        <div class="prop-title" style="min-height:36px; font-size:13.5px;" title="{r_row['title']}">{r_row['title'][:55]}...</div>
                        <div class="prop-price" style="color:#00ff88; font-size:17px;">{price_disp} <span style="font-size:11.5px; color:#64748b;">{rate_disp}</span></div>
                        <div class="prop-meta">
                            <span><i class="fa-solid fa-location-dot" style="color:#00d4ff;"></i> {r_row.get('district') or r_row.get('province') or 'Cambodia'}</span>
                            <span><i class="fa-solid fa-ruler-combined" style="color:#7c3aed;"></i> {r_row.get('area_sqm', 0):,.0f} m²</span>
                        </div>
                        <div style="margin-top:8px; padding-top:6px; border-top:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center;">
                            <span style="font-size:11px; color:#64748b;">Source: {str(r_row.get('source', '')).upper()}</span>
                            <a href="{r_row.get('url', '#')}" target="_blank" style="color:#00d4ff; font-size:12px; font-weight:600; text-decoration:none;">View Listing <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:10px;"></i></a>
                        </div>
                    </div>
                    """)

    with pulse_tab2:
        recent_rents = get_recent_properties(limit=6, listing_type="Rent")
        if not recent_rents.empty:
            r_cols_r = st.columns(3)
            for idx, r_row in recent_rents.iterrows():
                with r_cols_r[idx % 3]:
                    ptype = r_row.get("property_type", "Other")
                    ptype_icon = PROPERTY_TYPE_ICONS.get(ptype, "fa-solid fa-building")
                    thumb_url, fallback_url = get_property_thumbnail(r_row.get("image_url"), ptype)
                    price_disp = f"${r_row.get('price_usd', 0):,.0f}/mo" if pd.notnull(r_row.get('price_usd')) else "For Rent"
                    rate_disp = f"${r_row.get('price_per_sqm', 0):,.2f}/m²/mo" if pd.notnull(r_row.get('price_per_sqm')) and r_row.get('price_per_sqm') > 0 else ""
                    
                    render_html(f"""
                    <div class="prop-card">
                        <div class="prop-thumb-box" style="height:140px;">
                            <img src="{thumb_url}" onerror="this.onerror=null; this.src='{fallback_url}';" class="prop-thumb" alt="Thumbnail" />
                            <div class="prop-badge-overlay"><i class="{ptype_icon}"></i> {ptype}</div>
                            <div style="position:absolute; bottom:8px; right:8px; background:#0284c7; color:#fff; font-size:9.5px; font-weight:700; padding:2px 6px; border-radius:4px;">FOR RENT</div>
                        </div>
                        <div class="prop-title" style="min-height:36px; font-size:13.5px;" title="{r_row['title']}">{r_row['title'][:55]}...</div>
                        <div class="prop-price" style="color:#00d4ff; font-size:17px;">{price_disp} <span style="font-size:11.5px; color:#64748b;">{rate_disp}</span></div>
                        <div class="prop-meta">
                            <span><i class="fa-solid fa-location-dot" style="color:#00d4ff;"></i> {r_row.get('district') or r_row.get('province') or 'Cambodia'}</span>
                            <span><i class="fa-solid fa-ruler-combined" style="color:#7c3aed;"></i> {r_row.get('area_sqm', 0):,.0f} m²</span>
                        </div>
                        <div style="margin-top:8px; padding-top:6px; border-top:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center;">
                            <span style="font-size:11px; color:#64748b;">Source: {str(r_row.get('source', '')).upper()}</span>
                            <a href="{r_row.get('url', '#')}" target="_blank" style="color:#00d4ff; font-size:12px; font-weight:600; text-decoration:none;">View Listing <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:10px;"></i></a>
                        </div>
                    </div>
                    """)


# ==============================================================================
# WORKSPACE 2: 🕷️ SCRAPING OPERATIONS
# ==============================================================================
def render_scraper_hub():
    render_tab_hero(
        icon_class="fa-solid fa-network-wired",
        title="Multi-Portal Web Harvester & Scraper Operations",
        subtitle="Extract property listings directly from Cambodia's 5 premier portals into a centralized SQLite database.",
        badge_text="REST API & Browser Automation",
        color="#2563eb",
        bg_gradient="#eff6ff"
    )

    col1, col2 = st.columns([1.35, 0.9])

    with col1:
        st.markdown("<div style='font-size:13px; font-weight:700; color:#f1f5f9; margin-bottom:12px;'><i class='fa-solid fa-layer-group' style='color:#00d4ff; margin-right:8px;'></i>Select Target Property Portals</div>", unsafe_allow_html=True)
        selected_sources = []
        portal_cols = st.columns(2)

        source_keys = list(WEBSITES.keys())
        for idx, k in enumerate(source_keys):
            site = WEBSITES[k]
            site_color = site.get("color", "#2563eb")
            site_icon = site.get("icon", "fa-solid fa-globe")
            count_in_db = stats["by_source"].get(k, 0)
            with portal_cols[idx % 2]:
                st.markdown(f"""
                <div class="portal-card" style="border-left: 3px solid {site_color};">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                        <span style="font-weight:700; font-size:13.5px; color:#f1f5f9;">
                            <i class="{site_icon}" style="color:{site_color}; margin-right:6px;"></i>{site['name']}
                        </span>
                        <span style="background:rgba(17,19,24,0.7); color:#94a3b8; border:1px solid #e2e8f0; font-size:10px; font-weight:700; padding:2px 7px; border-radius:6px;">
                            {site['method']}
                        </span>
                    </div>
                    <div style="font-size:12px; color:#64748b; margin-bottom:4px;">
                        <i class="fa-solid fa-database" style="color:#94a3b8; margin-right:4px;"></i><b>{count_in_db:,}</b> listings currently in DB
                    </div>
                </div>
                """, unsafe_allow_html=True)
                is_checked = st.checkbox(
                    f"Enable {site['name']}",
                    value=(k in ["arc", "realestate", "cambodia_re"]),
                    key=f"check_{k}",
                    label_visibility="collapsed"
                )
                if is_checked:
                    selected_sources.append(k)

    with col2:
        st.markdown("<div class='glass-panel'>", unsafe_allow_html=True)
        st.markdown("<div style='font-size:13px; font-weight:700; color:#f1f5f9; margin-bottom:14px;'><i class='fa-solid fa-sliders' style='color:#00d4ff; margin-right:8px;'></i>Scraper Engine Strategy</div>", unsafe_allow_html=True)
        
        scrape_mode = st.radio(
            "Mining Strategy",
            options=["⛏️ Scrape Entire Website Database to Mine", "⚡ Custom Sample Harvest (Select Page Limit)"],
            index=0,
            help="Choose whether to mine the full catalog of target websites directly into your database or harvest a small sample of pages."
        )
        is_full_mine = ("Entire" in scrape_mode)

        scrape_category = st.selectbox(
            "Property Category",
            options=["All", "Land", "Condo", "House", "Villa", "Commercial"],
            index=0,
            help="Filter scraping by specific real estate category (where supported by portal API)."
        )

        if is_full_mine:
            full_depth = st.selectbox(
                "Full Database Mining Depth",
                options=[
                    "Exhaustive Mine (Auto-detect & Sync Entire Catalog ~8,700+ per portal)",
                    "Deep Mine (~100 Pages / Up to 4,000 listings per portal)",
                    "Standard Mine (~50 Pages / Up to 2,000 listings per portal)"
                ],
                index=0,
                help="Exhaustive Mine probes portal API headers and extracts all active listings from the website database."
            )

            if "Exhaustive" in full_depth:
                target_max_pages = 250
            elif "100" in full_depth:
                target_max_pages = 100
            else:
                target_max_pages = 50

            st.markdown(f"""
            <div style="background:rgba(0,255,136,0.08); border:1px solid rgba(0,255,136,0.2); border-radius:8px; padding:12px 14px; margin:14px 0; font-size:12px; color:#00ff88;">
                <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                    <span style="font-weight:700; color:#00ff88;"><i class="fa-solid fa-database" style="margin-right:4px;"></i>Full Catalog Mining:</span>
                    <b style="color:#00ff88;">ENABLED</b>
                </div>
                <div style="line-height:1.5; color:#047857; font-size:11.5px;">
                    • <b>ARC:</b> Full nationwide map database in 1 query (~2,560 listings)<br/>
                    • <b>Century 21:</b> Full WP REST catalog across ~176 pages (~8,760 listings)<br/>
                    • <b>Realestate.com.kh:</b> Auto-paginates until catalog end<br/>
                    • <b>Khmer24:</b> Paginated deep extraction with GPS coordinate resolution<br/>
                    • <b>Real-Time Streaming:</b> Each page is committed directly to SQLite so progress is never lost!
                </div>
            </div>
            """, unsafe_allow_html=True)
        else:
            target_max_pages = st.slider(
                "Pages per Portal",
                min_value=1,
                max_value=50,
                value=3,
                help="Number of pages to scrape per portal. For C21 & Realestate, each page contains 40-100 listings."
            )
            est_listings = target_max_pages * len(selected_sources) * 50
            st.markdown(f"""
            <div style="background:rgba(0,212,255,0.08); border:1px solid rgba(0,212,255,0.2); border-radius:8px; padding:12px 14px; margin:14px 0; font-size:12px; color:#00d4ff;">
                <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                    <span>Selected Portals:</span>
                    <b style="color:#00d4ff;">{len(selected_sources)} active</b>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span>Estimated Harvest:</span>
                    <b style="color:#00ff88;">~{est_listings:,} listings</b>
                </div>
            </div>
            """, unsafe_allow_html=True)

        btn_label = "⛏️ Start Full Database Mine" if is_full_mine else "🚀 Launch Scraper Engine"
        start_scrape = st.button(btn_label, type="primary", use_container_width=True)
        st.markdown("</div>", unsafe_allow_html=True)

    if start_scrape:
        if not selected_sources:
            st.warning("Please select at least one property portal to scrape.")
        else:
            manager = ScraperManager()
            prog_bar = st.progress(0.0)
            status_text = st.empty()

            def update_progress(val, msg):
                prog_bar.progress(val)
                status_text.info(f"⏳ {msg}")

            with st.spinner("Mining properties and streaming into database..."):
                results = manager.run_scrapers(
                    target_sources=selected_sources,
                    category=scrape_category,
                    max_pages=target_max_pages,
                    full_catalog=is_full_mine,
                    progress_callback=update_progress
                )

            status_text.success(
                f"✅ Scraping completed! Found **{results['total_found']}** listings, saved/updated **{results['total_saved']}** in database."
            )

            # Results summary breakdown
            res_df = []
            for s_key, data in results["by_source"].items():
                s_name = WEBSITES.get(s_key, {}).get("name", s_key)
                res_df.append({
                    "Portal": s_name,
                    "Status": data["status"],
                    "Listings Found": data["found"],
                    "Listings Saved": data["saved"],
                    "Error": data["error"] or "None"
                })
            st.dataframe(pd.DataFrame(res_df), use_container_width=True)
            st.rerun()

    # Historical Scrape Execution Logs
    st.markdown("<div style='margin-top:28px;'></div>", unsafe_allow_html=True)
    st.markdown("<div style='font-size:14px; font-weight:700; color:#f1f5f9; margin-bottom:12px;'><i class='fa-solid fa-list-check' style='color:#00d4ff; margin-right:8px;'></i>Recent Scrape Execution Audit Logs</div>", unsafe_allow_html=True)
    
    logs_df = get_scrape_logs(limit=25)
    if not logs_df.empty:
        st.dataframe(
            logs_df,
            use_container_width=True,
            column_config={
                "id": "Log ID",
                "source": "Portal Source",
                "status": "Execution Status",
                "items_found": st.column_config.NumberColumn("Found", format="%d"),
                "items_saved": st.column_config.NumberColumn("Saved/Updated", format="%d"),
                "started_at": "Started At",
                "finished_at": "Completed At",
                "error_message": "Errors / Diagnostics"
            }
        )
    else:
        st.info("No scrape execution logs recorded yet.")


# ==============================================================================
# WORKSPACE 3: 📋 MLS PROPERTY EXPLORER
# ==============================================================================
def render_property_explorer():
    render_tab_hero(
        icon_class="fa-solid fa-magnifying-glass-location",
        title="Property Listings Explorer",
        subtitle="Search, filter, and export live Cambodian real estate listings with multi-dimensional criteria.",
        badge_text="Interactive MLS",
        color="#059669",
        bg_gradient="#ecfdf5"
    )

    # Initial keyword from global search if applicable
    init_kw = st.session_state.get("global_search", "")

    # Top Filter Row
    f_col1, f_col2, f_col3, f_col4, f_col5 = st.columns([1.2, 1.3, 1.2, 1.3, 2])
    with f_col1:
        f_ltype = st.selectbox("Listing Type", ["All", "For Sale", "For Rent"])
    with f_col2:
        f_source = st.selectbox("Portal Source", ["All"] + list(WEBSITES.keys()), format_func=lambda x: WEBSITES.get(x, {}).get("name", x))
    with f_col3:
        f_type = st.selectbox("Property Type", PROPERTY_TYPES)
    with f_col4:
        f_prov = st.selectbox("Province", PROVINCES)
    with f_col5:
        f_search = st.text_input("Search Keyword", value=init_kw, placeholder="e.g. Sen Sok, BKK1, Villa, Land")

    # Clear global search after applying once
    if st.session_state.get("global_search"):
        st.session_state["global_search"] = ""

    # Numeric Range Expander
    with st.expander("⚙️ Advanced Range Filters (Price, Area, $/m²)", expanded=False):
        rf_col1, rf_col2, rf_col3 = st.columns(3)
        with rf_col1:
            p_step = 100.0 if f_ltype == "For Rent" else 10000.0
            p_label_min = "Min Rent ($/mo)" if f_ltype == "For Rent" else "Min Price ($)"
            p_label_max = "Max Rent ($/mo)" if f_ltype == "For Rent" else "Max Price ($)"
            p_min = st.number_input(p_label_min, min_value=0.0, value=0.0, step=p_step)
            p_max = st.number_input(p_label_max, min_value=0.0, value=0.0, step=p_step * 5)
        with rf_col2:
            a_min = st.number_input("Min Area (m²)", min_value=0.0, value=0.0, step=50.0)
            a_max = st.number_input("Max Area (m²)", min_value=0.0, value=0.0, step=200.0)
        with rf_col3:
            pp_step = 2.0 if f_ltype == "For Rent" else 100.0
            pp_label = "Min $/m²/mo" if f_ltype == "For Rent" else "Min $/m²"
            pp_max_label = "Max $/m²/mo" if f_ltype == "For Rent" else "Max $/m²"
            pp_min = st.number_input(pp_label, min_value=0.0, value=0.0, step=pp_step)
            pp_max = st.number_input(pp_max_label, min_value=0.0, value=0.0, step=pp_step * 5)
        f_urgent = st.checkbox("Show only listings with Urgency Badges (Urgent Sale / Below Market)")

    # Execute Query
    ltype_param = "Sale" if f_ltype == "For Sale" else ("Rent" if f_ltype == "For Rent" else None)
    df_props = query_properties(
        source=f_source if f_source != "All" else None,
        property_type=f_type if f_type != "All" else None,
        listing_type=ltype_param,
        province=f_prov if f_prov != "All" else None,
        min_price=p_min if p_min > 0 else None,
        max_price=p_max if p_max > 0 else None,
        min_sqm=a_min if a_min > 0 else None,
        max_sqm=a_max if a_max > 0 else None,
        min_pp_sqm=pp_min if pp_min > 0 else None,
        max_pp_sqm=pp_max if pp_max > 0 else None,
        search_text=f_search if f_search else None,
        urgency_only=f_urgent,
        limit=2000
    )

    st.markdown(f"<div style='font-size:14px; font-weight:600; color:#64748b; margin: 10px 0;'><i class='fa-solid fa-list-check' style='color:#00d4ff; margin-right:6px;'></i>Found <b style='color:#f1f5f9;'>{len(df_props):,}</b> matching listings</div>", unsafe_allow_html=True)

    # Export Buttons
    if not df_props.empty:
        exp_col1, exp_col2, _ = st.columns([1.8, 1.8, 4])
        with exp_col1:
            csv_data = df_props.to_csv(index=False).encode("utf-8-sig")
            st.download_button(
                label="📥 Download CSV",
                data=csv_data,
                file_name="cambodia_properties.csv",
                mime="text/csv",
                use_container_width=True
            )
        with exp_col2:
            buf = io.BytesIO()
            with pd.ExcelWriter(buf, engine="openpyxl") as writer:
                df_props.to_excel(writer, index=False, sheet_name="Properties")
            st.download_button(
                label="📥 Download Excel",
                data=buf.getvalue(),
                file_name="cambodia_properties.xlsx",
                mime="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                use_container_width=True
            )

    # View Mode Toggle
    view_mode = st.radio("Display Format", ["Interactive Grid", "Card Showcase", "🗺️ Geographic Map"], horizontal=True)

    if df_props.empty:
        st.info("No properties found matching your filter criteria. Try adjusting filters or scraping more portals.")
    elif view_mode == "Interactive Grid":
        # Pagination Settings
        total_items = len(df_props)
        if "grid_page_size" not in st.session_state:
            st.session_state.grid_page_size = 25

        page_size = st.session_state.grid_page_size
        total_pages = max(1, (total_items + page_size - 1) // page_size)

        if "grid_current_page" not in st.session_state:
            st.session_state.grid_current_page = 1
        if st.session_state.grid_current_page > total_pages:
            st.session_state.grid_current_page = total_pages
        if st.session_state.grid_current_page < 1:
            st.session_state.grid_current_page = 1

        # Synchronize session state keys for bidirectional inputs
        st.session_state["grid_jump_top"] = st.session_state.grid_current_page
        st.session_state["grid_jump_bot"] = st.session_state.grid_current_page
        st.session_state["grid_size_top"] = st.session_state.grid_page_size
        st.session_state["grid_size_bot"] = st.session_state.grid_page_size

        def go_next_page(max_p):
            if st.session_state.grid_current_page < max_p:
                st.session_state.grid_current_page += 1
                st.session_state["grid_jump_top"] = st.session_state.grid_current_page
                st.session_state["grid_jump_bot"] = st.session_state.grid_current_page

        def go_prev_page():
            if st.session_state.grid_current_page > 1:
                st.session_state.grid_current_page -= 1
                st.session_state["grid_jump_top"] = st.session_state.grid_current_page
                st.session_state["grid_jump_bot"] = st.session_state.grid_current_page

        def on_jump_change(source_key):
            val = st.session_state.get(source_key)
            if val is not None:
                new_p = max(1, min(int(val), total_pages))
                st.session_state.grid_current_page = new_p
                st.session_state["grid_jump_top"] = new_p
                st.session_state["grid_jump_bot"] = new_p

        def on_size_change(size_key):
            new_sz = st.session_state.get(size_key)
            if new_sz:
                st.session_state.grid_page_size = int(new_sz)
                st.session_state.grid_current_page = 1
                st.session_state["grid_size_top"] = int(new_sz)
                st.session_state["grid_size_bot"] = int(new_sz)
                st.session_state["grid_jump_top"] = 1
                st.session_state["grid_jump_bot"] = 1

        def render_pagination_bar(pos: str):
            c1, c2, c3 = st.columns([2, 3, 2.5])
            with c1:
                st.selectbox(
                    "Rows per page",
                    [15, 25, 50, 100],
                    key=f"grid_size_{pos}",
                    on_change=on_size_change,
                    args=(f"grid_size_{pos}",)
                )
            with c2:
                cur = st.session_state.grid_current_page
                st_idx = (cur - 1) * page_size + 1
                en_idx = min(cur * page_size, total_items)
                st.markdown(f"""
                <div style="display:flex; justify-content:center; align-items:center; height:100%; padding-top:24px;">
                    <span style="font-size:13px; color:#64748b;">
                        Showing <b style="color:#f1f5f9;">{st_idx:,} - {en_idx:,}</b> of <b style="color:#f1f5f9;">{total_items:,}</b> listings (Page <b style="color:#f1f5f9;">{cur}</b> / <b style="color:#f1f5f9;">{total_pages}</b>)
                    </span>
                </div>
                """, unsafe_allow_html=True)
            with c3:
                st.write("")
                bp, bj, bn = st.columns([1, 1.2, 1])
                with bp:
                    st.button(
                        "◀ Prev",
                        disabled=(st.session_state.grid_current_page <= 1),
                        use_container_width=True,
                        key=f"grid_prev_{pos}",
                        on_click=go_prev_page
                    )
                with bj:
                    st.number_input(
                        "Jump to page",
                        min_value=1,
                        max_value=total_pages,
                        key=f"grid_jump_{pos}",
                        on_change=on_jump_change,
                        args=(f"grid_jump_{pos}",),
                        label_visibility="collapsed"
                    )
                with bn:
                    st.button(
                        "Next ▶",
                        disabled=(st.session_state.grid_current_page >= total_pages),
                        use_container_width=True,
                        key=f"grid_next_{pos}",
                        on_click=go_next_page,
                        args=(total_pages,)
                    )

        # Top Pagination Bar
        render_pagination_bar("top")

        # Slice current page
        start_idx = (st.session_state.grid_current_page - 1) * page_size
        end_idx = min(start_idx + page_size, total_items)
        page_df = df_props.iloc[start_idx:end_idx]

        # Generate HTML table
        rows_html = []
        for _, row in page_df.iterrows():
            ptype = row.get("property_type", "Other")
            thumb_url, fallback_url = get_property_thumbnail(row.get("image_url"), ptype)
            src_key = row.get("source", "")
            src_cfg = WEBSITES.get(src_key, {"name": src_key.upper(), "color": "#2563eb", "icon": "fa-solid fa-globe"})
            
            price_val = row.get("price_usd")
            ltype = row.get("listing_type", "Sale")
            if ltype == "Rent":
                price_disp = f"${price_val:,.0f}/mo" if pd.notnull(price_val) else "For Rent"
                rate_val = row.get("price_per_sqm")
                rate_disp = f"${rate_val:,.2f}/m²/mo" if pd.notnull(rate_val) and rate_val > 0 else "—"
                status_badge = "<span style='background:rgba(0,212,255,0.08); color:#00d4ff; border:1px solid rgba(0,212,255,0.2); padding:2px 7px; border-radius:6px; font-size:11px; font-weight:700;'>FOR RENT</span>"
            else:
                price_disp = f"${price_val:,.0f}" if pd.notnull(price_val) else "Contact"
                rate_val = row.get("price_per_sqm")
                rate_disp = f"${rate_val:,.0f}/m²" if pd.notnull(rate_val) and rate_val > 0 else "—"
                status_badge = "<span style='background:rgba(0,255,136,0.08); color:#00ff88; border:1px solid rgba(0,255,136,0.2); padding:2px 7px; border-radius:6px; font-size:11px; font-weight:700;'>FOR SALE</span>"

            area_val = row.get("area_sqm")
            area_disp = f"{area_val:,.1f} m²" if pd.notnull(area_val) and area_val > 0 else "—"
            loc_disp = f"{row.get('district', '')}, {row.get('province', '')}".strip(", ") or "Cambodia"
            urgent_html = f"<span class='badge-urgent' style='font-size:10px; padding:2px 6px;'><i class='fa-solid fa-fire'></i> {row['urgency_tag']}</span>" if row.get('urgency_tag') else ""
            title_escaped = str(row.get('title', 'Untitled Property')).replace('"', '&quot;')
            url = row.get("url", "#")
            
            row_html = f"""
            <tr>
                <td style="color:#64748b; font-size:12px; font-weight:600;">#{row.get('id', '')}</td>
                <td>
                    <span style="display:inline-flex; align-items:center; gap:5px; background:rgba(0,212,255,0.08); color:#00d4ff; border:1px solid rgba(0,212,255,0.2); padding:3px 7px; border-radius:6px; font-size:11px; font-weight:700; white-space:nowrap;">
                        <i class="{src_cfg.get('icon', 'fa-solid fa-globe')}"></i> {src_cfg.get('name', src_key.upper())}
                    </span>
                </td>
                <td>
                    <div class="grid-title-box">
                        <img src="{thumb_url}" onerror="this.onerror=null; this.src='{fallback_url}';" class="grid-thumb" alt="Thumbnail" />
                        <div class="grid-title-text" style="color:#f1f5f9;" title="{title_escaped}">{title_escaped}</div>
                    </div>
                </td>
                <td>
                    <span style="background:rgba(17,19,24,0.7); border:1px solid #e2e8f0; padding:3px 7px; border-radius:6px; font-size:11px; color:#94a3b8; white-space:nowrap;">
                        {ptype}
                    </span>
                </td>
                <td style="font-weight:700; color:{'#2563eb' if ltype=='Rent' else '#059669'}; font-size:14px; white-space:nowrap;">{price_disp}</td>
                <td style="color:#94a3b8; white-space:nowrap;">{area_disp}</td>
                <td style="color:#d97706; font-weight:600; white-space:nowrap;">{rate_disp}</td>
                <td style="color:#64748b; font-size:12.5px; white-space:nowrap;">
                    <i class="fa-solid fa-location-dot" style="color:#00d4ff; margin-right:3px;"></i>{loc_disp}
                </td>
                <td style="white-space:nowrap;">{status_badge} {urgent_html}</td>
                <td style="text-align:center;">
                    <a href="{url}" target="_blank" class="grid-link-btn">
                        View <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:10px;"></i>
                    </a>
                </td>
            </tr>
            """
            rows_html.append(row_html)

        full_table_html = f"""
        <div class="grid-table-container">
            <table class="grid-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Portal</th>
                        <th>Property Title (Khmer)</th>
                        <th>Type</th>
                        <th>Price</th>
                        <th>Area</th>
                        <th>Rate</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    {''.join(rows_html)}
                </tbody>
            </table>
        </div>
        """
        render_html(full_table_html)

        # Bottom Pagination Bar
        render_pagination_bar("bot")

    elif view_mode == "Card Showcase":
        cols = st.columns(3)
        for idx, row in df_props.head(30).iterrows():
            with cols[idx % 3]:
                ptype = row.get("property_type", "Other")
                ptype_icon = PROPERTY_TYPE_ICONS.get(ptype, "fa-solid fa-building")
                ltype = row.get("listing_type", "Sale")
                thumb_url, fallback_url = get_property_thumbnail(row.get("image_url"), ptype)
                urgent_badge_html = f"<div class='prop-urgent-overlay'><i class='fa-solid fa-fire'></i> {row['urgency_tag']}</div>" if row.get('urgency_tag') else ""
                
                if ltype == "Rent":
                    rate_str = f"(${row['price_per_sqm']:,.2f}/m²/mo)" if pd.notnull(row.get('price_per_sqm')) else ""
                    price_box = f"<div class='prop-price' style='color:#00d4ff;'>${row['price_usd']:,.0f}/mo <span style='font-size:12px;color:#64748b;'>{rate_str}</span></div>"
                    ltype_badge_html = "<div style='position:absolute; bottom:8px; right:8px; background:#0284c7; color:#fff; font-size:10px; font-weight:700; padding:2px 7px; border-radius:4px;'>FOR RENT</div>"
                else:
                    rate_str = f"(${row['price_per_sqm']:,.0f}/m²)" if pd.notnull(row.get('price_per_sqm')) else ""
                    price_box = f"<div class='prop-price' style='color:#00ff88;'>${row['price_usd']:,.0f} <span style='font-size:12px;color:#64748b;'>{rate_str}</span></div>"
                    ltype_badge_html = "<div style='position:absolute; bottom:8px; right:8px; background:#059669; color:#fff; font-size:10px; font-weight:700; padding:2px 7px; border-radius:4px;'>FOR SALE</div>"

                src_key = row.get("source", "")
                src_cfg = WEBSITES.get(src_key, {"name": src_key.upper(), "color": "#2563eb", "icon": "fa-solid fa-globe"})
                
                render_html(f"""
                <div class="prop-card">
                    <div class="prop-thumb-box">
                        <img src="{thumb_url}" onerror="this.onerror=null; this.src='{fallback_url}';" class="prop-thumb" alt="Property Thumbnail"/>
                        <div class="prop-badge-overlay"><i class="{ptype_icon}"></i> {ptype}</div>
                        {ltype_badge_html}
                        {urgent_badge_html}
                    </div>
                    <div class="prop-title" title="{row['title']}">{row['title'][:70]}...</div>
                    {price_box}
                    <div class="prop-meta">
                        <span><i class="fa-solid fa-location-dot" style="color:#00d4ff; margin-right:4px;"></i>{row['district'] or row['province'] or 'Cambodia'}</span>
                        <span><i class="fa-solid fa-ruler-combined" style="color:#7c3aed; margin-right:4px;"></i>{f"{row['area_sqm']:,.0f} m²" if pd.notnull(row['area_sqm']) else 'N/A'}</span>
                    </div>
                    <div style="margin-top:10px; padding-top:8px; border-top:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center;">
                        <span style="font-size:11.5px; color:#64748b; display:flex; align-items:center; gap:5px;">
                            <i class="{src_cfg.get('icon', 'fa-solid fa-globe')}" style="color:{src_cfg.get('color', '#2563eb')};"></i>
                            <b>{src_cfg.get('name', src_key.upper())}</b>
                        </span>
                        <a href="{row['url']}" target="_blank" style="font-size:12.5px; color:#00d4ff; text-decoration:none; font-weight:600; display:flex; align-items:center; gap:5px;">
                            View Listing <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:11px;"></i>
                        </a>
                    </div>
                </div>
                """)
    else:
        # 🗺️ Geographic Map View
        with st.spinner("Geocoding property listings and rendering interactive map..."):
            geo_df = geocode_properties(df_props)

        m_col1, m_col2, m_col3, m_col4 = st.columns(4)
        with m_col1:
            render_html(f"""
            <div class="sidebar-stat-card" style="margin-bottom:12px;">
                <div class="sidebar-stat-icon" style="background:rgba(0,212,255,0.08); color:#00d4ff;">
                    <i class="fa-solid fa-map-pin"></i>
                </div>
                <div>
                    <div style="font-size:10px; color:#64748b; text-transform:uppercase;">Mapped Listings</div>
                    <div style="font-size:15px; font-weight:700; color:#f1f5f9;">{len(geo_df):,}</div>
                </div>
            </div>
            """)
        with m_col2:
            med_price = geo_df["price_usd"].median() if not geo_df["price_usd"].dropna().empty else 0
            render_html(f"""
            <div class="sidebar-stat-card" style="margin-bottom:12px;">
                <div class="sidebar-stat-icon" style="background:rgba(0,255,136,0.08); color:#00ff88;">
                    <i class="fa-solid fa-money-bill-wave"></i>
                </div>
                <div>
                    <div style="font-size:10px; color:#64748b; text-transform:uppercase;">Median Price</div>
                    <div style="font-size:15px; font-weight:700; color:#00ff88;">${med_price:,.0f}</div>
                </div>
            </div>
            """)
        with m_col3:
            med_sqm = geo_df["price_per_sqm"].median() if not geo_df["price_per_sqm"].dropna().empty else 0
            render_html(f"""
            <div class="sidebar-stat-card" style="margin-bottom:12px;">
                <div class="sidebar-stat-icon" style="background:#fffbeb; color:#d97706;">
                    <i class="fa-solid fa-tag"></i>
                </div>
                <div>
                    <div style="font-size:10px; color:#64748b; text-transform:uppercase;">Median Rate</div>
                    <div style="font-size:15px; font-weight:700; color:#d97706;">${med_sqm:,.0f}/m²</div>
                </div>
            </div>
            """)
        with m_col4:
            valid_dists = geo_df["district"].replace("", pd.NA).dropna()
            top_loc = valid_dists.mode()[0] if not valid_dists.empty else (geo_df["province"].mode()[0] if not geo_df["province"].empty else "Cambodia")
            render_html(f"""
            <div class="sidebar-stat-card" style="margin-bottom:12px;">
                <div class="sidebar-stat-icon" style="background:rgba(168,85,247,0.08); color:#7c3aed;">
                    <i class="fa-solid fa-location-dot"></i>
                </div>
                <div>
                    <div style="font-size:10px; color:#64748b; text-transform:uppercase;">Top Hotspot</div>
                    <div style="font-size:14px; font-weight:700; color:#7c3aed;">{top_loc}</div>
                </div>
            </div>
            """)

        map_ctrl1, map_ctrl2 = st.columns([3, 1])
        with map_ctrl1:
            st.markdown(
                f"<div style='font-size:13px; color:#94a3b8; padding-top:6px;'>"
                f"<i class='fa-solid fa-map-location-dot' style='color:#38bdf8; margin-right:6px;'></i>"
                f"Interactive Cluster Map &middot; Mapped <b>{len(geo_df):,}</b> properties across Cambodia"
                f"</div>",
                unsafe_allow_html=True
            )
        with map_ctrl2:
            map_pin_limit = st.selectbox(
                "Map Pin Density",
                options=["All Mapped Listings", "Top 1,500", "Top 1,000", "Top 500"],
                index=0,
                help="Control the maximum number of properties rendered on the map."
            )

        limit_lookup = {
            "All Mapped Listings": None,
            "Top 1,500": 1500,
            "Top 1,000": 1000,
            "Top 500": 500
        }
        max_pins_val = limit_lookup.get(map_pin_limit)

        map_html = generate_leaflet_map_html(geo_df, height_px=620, max_pins=max_pins_val)
        components.html(map_html, height=635, scrolling=False)

        render_html("""
        <div style="display:flex; flex-wrap:wrap; gap:14px; align-items:center; background:#ffffff; border:1px solid #e2e8f0; border-radius:10px; padding:10px 14px; margin-top:8px; font-size:12px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
            <span style="font-weight:700; color:#f1f5f9; margin-right:4px;"><i class="fa-solid fa-layer-group" style="color:#00d4ff;"></i> Pin Legend:</span>
            <span style="display:flex; align-items:center; gap:5px; color:#475569;"><span style="width:9px; height:9px; border-radius:50%; background:#10b981; display:inline-block;"></span> Land</span>
            <span style="display:flex; align-items:center; gap:5px; color:#475569;"><span style="width:9px; height:9px; border-radius:50%; background:#0284c7; display:inline-block;"></span> Condo</span>
            <span style="display:flex; align-items:center; gap:5px; color:#475569;"><span style="width:9px; height:9px; border-radius:50%; background:#7c3aed; display:inline-block;"></span> Villa</span>
            <span style="display:flex; align-items:center; gap:5px; color:#475569;"><span style="width:9px; height:9px; border-radius:50%; background:#d97706; display:inline-block;"></span> House</span>
            <span style="display:flex; align-items:center; gap:5px; color:#475569;"><span style="width:9px; height:9px; border-radius:50%; background:#db2777; display:inline-block;"></span> Shophouse</span>
            <span style="display:flex; align-items:center; gap:5px; color:#475569;"><span style="width:9px; height:9px; border-radius:50%; background:#dc2626; display:inline-block;"></span> Urgent Deal</span>
        </div>
        """)


# ==============================================================================
# WORKSPACE 4: ⚖️ CMA VALUATION STUDIO
# ==============================================================================
def render_cma_studio():
    render_tab_hero(
        icon_class="fa-solid fa-scale-balanced",
        title="Comparable Market Analysis (CMA) Studio",
        subtitle="Automated valuation algorithm analyzing empirical market comps in the same Khan and size bracket.",
        badge_text="AI Valuation Engine",
        color="#4f46e5",
        bg_gradient="rgba(79, 70, 229, 0.08)"
    )

    render_html("""
    <div style="background:rgba(0,212,255,0.08); border:1px solid rgba(0,212,255,0.2); border-radius:8px; padding:10px 14px; margin-bottom:14px; display:flex; align-items:center; gap:10px; font-size:12.5px; color:#00d4ff;">
        <i class="fa-solid fa-circle-info" style="color:#00d4ff; font-size:15px;"></i>
        <span><b>CMA Valuation Standard:</b> Condos and Apartments are strictly excluded from Comparable Market Analysis because high-rise strata-title units cannot be directly compared with landed property valuations.</span>
    </div>
    """)

    cma_mode = st.radio("Input Source", ["Enter Custom Property Details", "Select from Existing Database"], horizontal=True)

    cma_col_left, cma_col_right = st.columns([1.2, 0.8])

    with cma_col_left:
        st.markdown("<div class='glass-panel'>", unsafe_allow_html=True)
        st.markdown("<div style='font-size:13px; font-weight:700; color:#f1f5f9; margin-bottom:12px;'><i class='fa-solid fa-sliders' style='color:#a855f7; margin-right:8px;'></i>Valuation Subject Property Specifications</div>", unsafe_allow_html=True)

        if cma_mode == "Enter Custom Property Details":
            target_ltype_choice = st.radio("Valuation Purpose", ["🏷️ For Sale (Purchase Valuation)", "🔑 For Rent (Lease Valuation)"], horizontal=True)
            target_ltype = "Sale" if "Sale" in target_ltype_choice else "Rent"

            cf_c1, cf_c2 = st.columns(2)
            with cf_c1:
                target_prov = st.selectbox("Province", ["Phnom Penh", "Siem Reap", "Preah Sihanouk", "Kandal", "Kampot"], key="cma_prov")
                target_type = st.selectbox("Property Type", ["Land", "Villa", "House", "Shophouse", "Commercial", "Warehouse", "Borey"], key="cma_type")
                target_area = st.number_input("Target Area (m²)", min_value=10.0, max_value=500000.0, value=120.0, step=10.0)
            with cf_c2:
                target_dist = st.selectbox("District (Khan)", ["All"] + PHNOM_PENH_DISTRICTS, key="cma_dist")
                p_lbl = "Target Monthly Rent ($/mo) [Optional]" if target_ltype == "Rent" else "Asking Sale Price ($) [Optional]"
                p_stp = 50.0 if target_ltype == "Rent" else 10000.0
                target_price = st.number_input(p_lbl, min_value=0.0, value=0.0, step=p_stp)
                target_tol = st.slider("Comparable Size Tolerance (±%)", min_value=10, max_value=50, value=25)

        else:
            all_props = query_properties(limit=1000)
            if all_props.empty:
                st.warning("Database is empty. Please scrape some listings first.")
                st.stop()

            all_props = all_props[~all_props["property_type"].isin(["Condo", "Apartment", "Studio", "Penthouse", "Loft"])]
            if all_props.empty:
                st.warning("No landed property listings available for CMA in database.")
                st.stop()

            prop_options = {
                f"#{r['id']} [{r.get('listing_type', 'Sale')}] - {r['title'][:45]} | {r['district']} | {r['area_sqm']}m² | ${r['price_usd']:,.0f}{'/mo' if r.get('listing_type')=='Rent' else ''}": r
                for _, r in all_props.iterrows() if r["area_sqm"] and r["area_sqm"] > 0
            }
            selected_prop_label = st.selectbox("Select Property to Analyze", list(prop_options.keys()))
            selected_row = prop_options[selected_prop_label]

            target_ltype = selected_row.get("listing_type", "Sale")
            target_prov = selected_row["province"] or "Phnom Penh"
            target_dist = selected_row["district"]
            target_type = selected_row["property_type"]
            target_area = float(selected_row["area_sqm"])
            target_price = float(selected_row["price_usd"] or 0)
            target_tol = 25

        st.markdown("<div style='margin-top:14px;'>", unsafe_allow_html=True)
        run_cma = st.button("⚖️ Run Automated Valuation", type="primary", use_container_width=True)
        st.markdown("</div></div>", unsafe_allow_html=True)

    with cma_col_right:
        st.markdown(f"""
        <div class="glass-panel" style="border-top: 3px solid #a855f7;">
            <div style="font-size:13px; font-weight:700; color:#f1f5f9; margin-bottom:12px; display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-brain" style="color:#a855f7;"></i>
                Valuation Studio Intelligence
            </div>
            <div style="background:rgba(17,19,24,0.5); border:1px solid #e2e8f0; border-radius:10px; padding:14px; margin-bottom:12px;">
                <div style="font-size:10.5px; color:#64748b; text-transform:uppercase; font-weight:700; letter-spacing:0.5px;">Target Specifications</div>
                <div style="font-size:17px; font-weight:800; color:#f1f5f9; margin:4px 0;">{target_area:,.0f} m² {target_type}</div>
                <div style="font-size:12.5px; color:#a855f7; font-weight:600;"><i class="fa-solid fa-location-dot" style="margin-right:4px;"></i>{target_dist if target_dist != 'All' else 'All Districts'}, {target_prov}</div>
            </div>
            <div style="font-size:12px; color:#475569; line-height:1.6;">
                <div style="margin-bottom:8px;"><i class="fa-solid fa-circle-check" style="color:#00ff88; margin-right:6px;"></i><b>Pure Comp Matching:</b> Searches empirical market listings in the same Khan within <b>±{target_tol}%</b> size bracket.</div>
                <div style="margin-bottom:8px;"><i class="fa-solid fa-shield-halved" style="color:#dc2626; margin-right:6px;"></i><b>Strata Protection:</b> High-rise condominiums are strictly isolated from landed real estate.</div>
                <div><i class="fa-solid fa-scale-balanced" style="color:#00d4ff; margin-right:6px;"></i><b>Triple Range:</b> Calculates Conservative (25th Pct), Fair Market (Median), and Premium (75th Pct).</div>
            </div>
        </div>
        """, unsafe_allow_html=True)

    if run_cma:
        with st.spinner("Finding comparable properties and calculating benchmarks..."):
            cma_res = generate_cma_report(
                target_area_sqm=target_area,
                province=target_prov,
                district=target_dist if target_dist != "All" else None,
                property_type=target_type,
                listing_type=target_ltype,
                target_price_usd=target_price if target_price > 0 else None,
                size_tolerance_pct=target_tol / 100.0
            )

        if "error" in cma_res or cma_res.get("count", 0) == 0:
            st.error(cma_res.get("message", "No comparable listings found."))
        else:
            is_rent = (target_ltype == "Rent")
            unit_suffix = "/mo" if is_rent else ""
            rate_unit = "$/m²/mo" if is_rent else "$/m²"
            st.success(f"Analyzed **{cma_res['count']}** comparable **{target_ltype.upper()}** listings within size range.")

            m = cma_res["metrics"]
            v = cma_res["valuation"]

            v_col1, v_col2, v_col3 = st.columns(3)
            with v_col1:
                render_html(f"""
                <div class="metric-box">
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                        <i class="fa-solid fa-shield-halved" style="color:#64748b; font-size:16px;"></i>
                        <span class="metric-label">Conservative (25th Pct)</span>
                    </div>
                    <div class="metric-value" style="color:#f1f5f9;">${v['conservative_usd']:,.0f}{unit_suffix}</div>
                    <div style="font-size:13px; color:#64748b; margin-top:4px;"><i class="fa-solid fa-tag" style="margin-right:4px; color:#94a3b8;"></i>Rate: ${m['p25_pp_sqm']:,.1f} {rate_unit}</div>
                </div>
                """)
            with v_col2:
                render_html(f"""
                <div class="metric-box" style="border-left: 3px solid {'#0284c7' if is_rent else '#059669'};">
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                        <i class="fa-solid fa-scale-balanced" style="color:{'#0284c7' if is_rent else '#059669'}; font-size:16px;"></i>
                        <span class="metric-label" style="color:{'#0284c7' if is_rent else '#059669'}; font-weight:700;">Fair Market {'Rent' if is_rent else 'Value'} (Median)</span>
                    </div>
                    <div class="metric-value" style="color:{'#0284c7' if is_rent else '#059669'};">${v['fair_market_usd']:,.0f}{unit_suffix}</div>
                    <div style="font-size:13px; color:#64748b; margin-top:4px;"><i class="fa-solid fa-tag" style="margin-right:4px; color:{'#0284c7' if is_rent else '#059669'};"></i>Rate: ${m['median_pp_sqm']:,.1f} {rate_unit}</div>
                </div>
                """)
            with v_col3:
                render_html(f"""
                <div class="metric-box">
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                        <i class="fa-solid fa-crown" style="color:#d97706; font-size:16px;"></i>
                        <span class="metric-label">Premium (75th Pct)</span>
                    </div>
                    <div class="metric-value" style="color:#d97706;">${v['premium_usd']:,.0f}{unit_suffix}</div>
                    <div style="font-size:13px; color:#64748b; margin-top:4px;"><i class="fa-solid fa-tag" style="margin-right:4px; color:#d97706;"></i>Rate: ${m['p75_pp_sqm']:,.1f} {rate_unit}</div>
                </div>
                """)

            if cma_res.get("variance"):
                var = cma_res["variance"]
                st.markdown("<div style='margin-top:20px;'></div>", unsafe_allow_html=True)
                st.subheader("🎯 Asking Price vs Market Valuation")
                diff = var["diff_pct"]
                if diff < -10:
                    st.success(f"🔥 **Great Deal Opportunity!** Asking price is **{abs(diff):.1f}% BELOW** Fair Market Valuation (${var['target_price']:,.0f} vs ${v['fair_market_usd']:,.0f}).")
                elif diff > 10:
                    st.warning(f"⚠️ **Priced Above Market.** Asking price is **{diff:.1f}% ABOVE** Fair Market Valuation (${var['target_price']:,.0f} vs ${v['fair_market_usd']:,.0f}).")
                else:
                    st.info(f"⚖️ **Fair Price.** Asking price matches market expectations (within {diff:.1f}%).")

            st.markdown("---")
            st.subheader("🔎 Top Comparable Properties Used")
            comps = cma_res.get("comps_df")
            if comps is not None and not comps.empty:
                comp_view = st.radio("Comps View", ["Visual Cards", "Table View"], horizontal=True, key="cma_comp_view")
                if comp_view == "Visual Cards":
                    c_cols = st.columns(3)
                    for c_idx, (_, c_row) in enumerate(comps.head(9).iterrows()):
                        with c_cols[c_idx % 3]:
                            c_ptype = c_row.get("property_type", "Other")
                            c_icon = PROPERTY_TYPE_ICONS.get(c_ptype, "fa-solid fa-building")
                            c_src = c_row.get("source", "")
                            c_src_cfg = WEBSITES.get(c_src, {"name": c_src.upper(), "color": "#38bdf8", "icon": "fa-solid fa-globe"})
                            c_thumb, c_fallback = get_property_thumbnail(c_row.get("image_url"), c_ptype)
                            
                            render_html(f"""
                            <div class="prop-card">
                                <div class="prop-thumb-box" style="height: 140px;">
                                    <img src="{c_thumb}" onerror="this.onerror=null; this.src='{c_fallback}';" class="prop-thumb" alt="Comp thumbnail"/>
                                    <div class="prop-badge-overlay"><i class="{c_icon}"></i> {c_ptype}</div>
                                    <div class="prop-urgent-overlay" style="background:#2563eb;"><i class="fa-solid fa-bullseye"></i> {c_row['similarity_score']:.0f}% Match</div>
                                </div>
                                <div class="prop-title" style="min-height:36px; font-size:14px;" title="{c_row['title']}">{c_row['title'][:55]}...</div>
                                <div class="prop-price" style="font-size:17px;">${c_row['price_usd']:,.0f} <span style="font-size:12px; color:#94a3b8;">(${c_row['price_per_sqm']:,.1f}/m²)</span></div>
                                <div class="prop-meta">
                                    <span><i class="fa-solid fa-location-dot" style="color:#38bdf8; margin-right:4px;"></i>{c_row['district'] or c_row['province']}</span>
                                    <span><i class="fa-solid fa-ruler-combined" style="color:#a855f7; margin-right:4px;"></i>{c_row['area_sqm']:,.0f} m²</span>
                                </div>
                                <div style="margin-top:10px; padding-top:8px; border-top:1px solid rgba(255,255,255,0.06); display:flex; justify-content:space-between; align-items:center;">
                                    <span style="font-size:11px; color:#64748b; display:flex; align-items:center; gap:5px;">
                                        <i class="{c_src_cfg.get('icon', 'fa-solid fa-globe')}" style="color:{c_src_cfg.get('color', '#2563eb')};"></i>
                                        <b>{c_src_cfg.get('name', c_src.upper())}</b>
                                    </span>
                                    <a href="{c_row['url']}" target="_blank" style="font-size:12px; color:#00d4ff; text-decoration:none; font-weight:600; display:flex; align-items:center; gap:4px;">
                                        Details <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:10px;"></i>
                                    </a>
                                </div>
                            </div>
                            """)
                else:
                    st.dataframe(
                        comps[["id", "source", "title", "district", "price_usd", "area_sqm", "price_per_sqm", "similarity_score", "url"]],
                        use_container_width=True,
                        column_config={
                            "url": st.column_config.LinkColumn("Listing Link", display_text="Open Listing"),
                            "price_usd": st.column_config.NumberColumn("Price ($)", format="$%d"),
                            "price_per_sqm": st.column_config.NumberColumn("Price/m²", format="$%.1f/m²"),
                            "area_sqm": st.column_config.NumberColumn("Area", format="%.1f m²"),
                            "similarity_score": st.column_config.ProgressColumn("Similarity", min_value=0, max_value=100, format="%d%%")
                        }
                    )


# ==============================================================================
# WORKSPACE 5: 💎 DEAL FINDER & SCREENER
# ==============================================================================
def render_deal_finder():
    render_tab_hero(
        icon_class="fa-solid fa-gem",
        title="Hot Deals & Below-Market Arbitrage Screener",
        subtitle="Algorithmic detection of listings trading at steep discounts relative to their district median $/m² rate.",
        badge_text="Arbitrage & Distressed Assets",
        color="#db2777",
        bg_gradient="rgba(219, 39, 119, 0.08)"
    )

    deal_market = st.radio("Deal Target", ["🏷️ Below-Market Purchase Deals (For Sale)", "🔑 Best Rental Bargains (For Rent)"], horizontal=True)
    deal_ltype = "Sale" if "Sale" in deal_market else "Rent"

    df_c1, df_c2, df_c3, df_c4 = st.columns(4)
    with df_c1:
        deal_prov = st.selectbox("Province Filter", PROVINCES, key="deal_prov")
    with df_c2:
        deal_type = st.selectbox("Property Type Filter", PROPERTY_TYPES, key="deal_type")
    with df_c3:
        deal_min_disc = st.slider("Min Discount vs Median (%)", min_value=5, max_value=60, value=15)
    with df_c4:
        d_p_step = 100.0 if deal_ltype == "Rent" else 50000.0
        d_p_lbl = "Max Monthly Rent ($/mo)" if deal_ltype == "Rent" else "Max Purchase Budget ($)"
        deal_max_price = st.number_input(d_p_lbl, min_value=0.0, value=0.0, step=d_p_step)

    deals = find_good_deals(
        province=deal_prov if deal_prov != "All" else None,
        property_type=deal_type if deal_type != "All" else None,
        listing_type=deal_ltype,
        min_discount_pct=deal_min_disc,
        max_price=deal_max_price if deal_max_price > 0 else None,
        limit=60
    )

    st.markdown(f"<div style='font-size:14px; font-weight:700; color:#f1f5f9; margin: 10px 0;'><i class='fa-solid fa-fire' style='color:#ff006e; margin-right:6px;'></i>Identified <b>{len(deals)}</b> High-Potential {deal_ltype.upper()} Opportunities</div>", unsafe_allow_html=True)

    if deals.empty:
        st.info(f"No {deal_ltype.lower()} deals found with the current discount threshold. Try lowering the threshold or scraping more data.")
    else:
        for _, deal in deals.iterrows():
            disc = deal["discount_pct"]
            badge_color = "#7c3aed" if disc >= 30 else "#059669"
            d_ptype = deal.get("property_type", "Other")
            d_icon = PROPERTY_TYPE_ICONS.get(d_ptype, "fa-solid fa-building")
            thumb_url, fallback_url = get_property_thumbnail(deal.get("image_url"), d_ptype)
            urgent_badge_html = f"<span class='badge-urgent'><i class='fa-solid fa-fire'></i> {deal['urgency_tag']}</span>" if deal.get('urgency_tag') else ""
            
            d_src = deal.get("source", "")
            d_src_cfg = WEBSITES.get(d_src, {"name": d_src.upper(), "color": "#2563eb", "icon": "fa-solid fa-globe"})

            price_str = f"${deal['price_usd']:,.0f}/mo" if deal_ltype == "Rent" else f"${deal['price_usd']:,.0f}"
            rate_unit = "$/m²/mo" if deal_ltype == "Rent" else "$/m²"
            rate_fmt = f"{deal['price_per_sqm']:,.2f}" if deal_ltype == "Rent" else f"{deal['price_per_sqm']:,.0f}"
            med_fmt = f"{deal['benchmark_median_pp_sqm']:,.2f}" if deal_ltype == "Rent" else f"{deal['benchmark_median_pp_sqm']:,.0f}"

            render_html(f"""
            <div class="prop-card" style="border-left: 4px solid {badge_color}; margin-bottom: 16px;">
                <div style="display: flex; gap: 16px; flex-wrap: wrap;">
                    <div style="width: 170px; height: 125px; border-radius: 8px; overflow: hidden; flex-shrink: 0; position: relative; background-color: #f1f5f9;">
                        <img src="{thumb_url}" onerror="this.onerror=null; this.src='{fallback_url}';" style="width: 100%; height: 100%; object-fit: cover;" alt="Deal thumbnail"/>
                        <div class="prop-badge-overlay" style="top: 6px; left: 6px; font-size: 10px;"><i class="{d_icon}"></i> {d_ptype}</div>
                    </div>
                    <div style="flex: 1; min-width: 250px; display: flex; flex-direction: column; justify-content: space-between;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px;">
                            <div>
                                <span class="badge-deal"><i class="fa-solid fa-gem"></i> {deal['deal_rating']}</span>
                                {urgent_badge_html}
                                <h4 style="margin: 6px 0 4px 0; color:#f1f5f9; font-size: 16px; font-weight:700;">{deal['title']}</h4>
                            </div>
                            <div style="text-align: right; white-space: nowrap;">
                                <div style="font-size: 22px; font-weight: 800; color: {'#0284c7' if deal_ltype=='Rent' else '#059669'};">{price_str}</div>
                                <div style="font-size: 12px; color: #0284c7; font-weight: 700; display:flex; align-items:center; justify-content:flex-end; gap:4px;">
                                    <i class="fa-solid fa-arrow-trend-down"></i> {disc:.1f}% BELOW MEDIAN
                                </div>
                            </div>
                        </div>
                        <div style="display: flex; flex-wrap: wrap; gap: 15px; font-size: 13px; color: #64748b; margin: 6px 0;">
                            <div><i class="fa-solid fa-location-dot" style="color:#00d4ff; margin-right:4px;"></i>{deal['district'] or deal['province']}</div>
                            <div><i class="fa-solid fa-ruler-combined" style="color:#7c3aed; margin-right:4px;"></i>{deal['area_sqm']:,.0f} m²</div>
                            <div><i class="fa-solid fa-tag" style="color:#00ff88; margin-right:4px;"></i><b>${rate_fmt} {rate_unit}</b> (Median: ${med_fmt} {rate_unit})</div>
                            <div><i class="{d_src_cfg.get('icon', 'fa-solid fa-globe')}" style="color:{d_src_cfg.get('color', '#2563eb')}; margin-right:4px;"></i>{d_src.upper()}</div>
                        </div>
                        <div style="text-align: right;">
                            <a href="{deal['url']}" target="_blank" style="color: #2563eb; font-size: 13px; text-decoration: none; font-weight: 600; display:inline-flex; align-items:center; gap:5px;">
                                Open Listing Details <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:11px;"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            """)


# ==============================================================================
# WORKSPACE 6: 📐 LAND VALUATION ESTIMATOR
# ==============================================================================
def render_land_estimator():
    render_tab_hero(
        icon_class="fa-solid fa-draw-polygon",
        title="Cambodian Land Valuation Estimator",
        subtitle="Appraisal benchmark for land parcels based on empirical comps, frontage road access grade, and plot area.",
        badge_text="Land Appraisal Model",
        color="#d97706",
        bg_gradient="rgba(217, 119, 6, 0.08)"
    )

    l_col1, l_col2 = st.columns([1, 1])

    with l_col1:
        st.subheader("Land Specifications")
        l_prov = st.selectbox("Province", ["Phnom Penh", "Siem Reap", "Preah Sihanouk", "Kandal", "Kampot", "Battambang", "Other"], key="land_prov")
        l_dist = st.selectbox("District / Khan", ["All"] + PHNOM_PENH_DISTRICTS, key="land_dist")
        l_area = st.number_input("Land Size (m²)", min_value=10.0, max_value=1000000.0, value=500.0, step=50.0)
        l_road = st.selectbox("Road Access / Location Grade", [
            "Standard Road",
            "Main Blvd / Commercial Frontage",
            "Secondary Road (8m-12m)",
            "Sub-lane / Residential"
        ])

        run_est = st.button("📐 Calculate Land Price", type="primary", use_container_width=True)

    with l_col2:
        st.subheader("Valuation Benchmark")
        if run_est or True:
            land_est = estimate_land_price(
                area_sqm=l_area,
                province=l_prov,
                district=l_dist if l_dist != "All" else None,
                road_type=l_road
            )

            r = land_est["rates_pp_sqm"]
            tot = land_est["estimated_totals"]

            render_html(f"""
            <div style="background: #ffffff; border:1px solid #e2e8f0; border-radius:14px; padding:20px; box-shadow: 0 4px 15px rgba(0,0,0,0.04); border-top: 4px solid #00ff88;">
                <div style="color:#00ff88; font-size:12.5px; text-transform:uppercase; font-weight:700; display:flex; align-items:center; gap:6px;">
                    <i class="fa-solid fa-map-location-dot"></i> Fair Market Land Valuation ({l_area:,.0f} m²)
                </div>
                <div style="font-size:32px; font-weight:800; color:#f1f5f9; margin:8px 0 4px 0;">${tot['fair_market']:,.0f}</div>
                <div style="font-size:15px; color:#00ff88; font-weight:600; display:flex; align-items:center; gap:6px;">
                    <i class="fa-solid fa-tag" style="font-size:13px;"></i> ${r['fair_market']:,.1f} per m²
                </div>
                <hr style="border:none; border-top:1px solid rgba(255,255,255,0.06); margin:14px 0;"/>
                <div style="display:flex; justify-content:space-between; font-size:14px;">
                    <div>
                        <span style="color:#64748b; font-size:12px; display:flex; align-items:center; gap:4px;">
                            <i class="fa-solid fa-shield-halved"></i> Conservative:
                        </span>
                        <b style="color:#f1f5f9;">${tot['conservative']:,.0f}</b> <span style="color:#64748b; font-size:12px;">(${r['conservative']:,.1f}/m²)</span>
                    </div>
                    <div>
                        <span style="color:#64748b; font-size:12px; display:flex; align-items:center; gap:4px;">
                            <i class="fa-solid fa-crown" style="color:#d97706;"></i> Premium:
                        </span>
                        <b style="color:#d97706;">${tot['premium']:,.0f}</b> <span style="color:#64748b; font-size:12px;">(${r['premium']:,.1f}/m²)</span>
                    </div>
                </div>
            </div>
            """)

    st.markdown("---")
    st.subheader(f"📊 {l_prov} District Land Price Index")
    benchmarks_df = get_district_land_benchmarks(province=l_prov)
    if not benchmarks_df.empty:
        st.dataframe(
            benchmarks_df,
            use_container_width=True,
            column_config={
                "district": "District / Khan",
                "listing_count": "Sample Comps Count",
                "min_pp_sqm": st.column_config.NumberColumn("Min $/m²", format="$%d"),
                "avg_pp_sqm": st.column_config.NumberColumn("Average $/m²", format="$%d"),
                "max_pp_sqm": st.column_config.NumberColumn("Max $/m²", format="$%d")
            }
        )


# ==============================================================================
# WORKSPACE 7: 📊 MARKET INTELLIGENCE HUB
# ==============================================================================
def render_market_analytics():
    render_tab_hero(
        icon_class="fa-solid fa-chart-line",
        title="Market Insights & Macro Analytics",
        subtitle="Cross-portal price trends, property distributions, and price per sqm comparisons.",
        badge_text="Market Telemetry",
        color="#7c3aed",
        bg_gradient="rgba(124, 58, 237, 0.08)"
    )

    market_view = st.radio("Analytics Market Segment", ["🏷️ Properties For Sale (Capital Values)", "🔑 Rental Market (Monthly Yields)"], horizontal=True)
    is_rent_analytics = ("Rental" in market_view)
    target_ltype = "Rent" if is_rent_analytics else "Sale"
    df_analytics = query_properties(listing_type=target_ltype, limit=5000)

    if df_analytics.empty:
        st.info(f"No {target_ltype.lower()} data available yet. Please run scrapers from Scraping Operations to populate market analytics.")
    else:
        m_col1, m_col2, m_col3 = st.columns(3)
        with m_col1:
            if is_rent_analytics:
                med_val = df_analytics["price_usd"].median()
                render_html(f"""
                <div class="metric-box">
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                        <i class="fa-solid fa-money-bill-wave" style="color:#00d4ff; font-size:16px;"></i>
                        <span class="metric-label">Median Monthly Rent</span>
                    </div>
                    <div class="metric-value" style="color:#00d4ff;">${med_val:,.0f} <span style="font-size:13px; color:#64748b;">/ mo</span></div>
                </div>
                """)
            else:
                med_val = df_analytics["price_usd"].median()
                render_html(f"""
                <div class="metric-box">
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                        <i class="fa-solid fa-tag" style="color:#00ff88; font-size:16px;"></i>
                        <span class="metric-label">Median Sale Price</span>
                    </div>
                    <div class="metric-value" style="color:#00ff88;">${med_val:,.0f}</div>
                </div>
                """)

        with m_col2:
            med_sqm = df_analytics["price_per_sqm"].median()
            unit_disp = "$/m²/mo" if is_rent_analytics else "$/m²"
            render_html(f"""
            <div class="metric-box">
                <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                    <i class="fa-solid fa-chart-simple" style="color:#d97706; font-size:16px;"></i>
                    <span class="metric-label">Median Unit Rate</span>
                </div>
                <div class="metric-value" style="color:#d97706;">${med_sqm:,.1f} <span style="font-size:13px; color:#64748b;">{unit_disp}</span></div>
            </div>
            """)

        with m_col3:
            total_active = len(df_analytics)
            render_html(f"""
            <div class="metric-box">
                <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                    <i class="fa-solid fa-cubes" style="color:#7c3aed; font-size:16px;"></i>
                    <span class="metric-label">Active {target_ltype.upper()} Data Points</span>
                </div>
                <div class="metric-value" style="color:#7c3aed;">{total_active:,}</div>
            </div>
            """)

        st.markdown("<div style='margin-top:20px;'></div>", unsafe_allow_html=True)

        a_col1, a_col2 = st.columns(2)
        with a_col1:
            chart_title = "Median Monthly Rent ($/mo) by Property Type" if is_rent_analytics else "Price per m² ($/m²) by Property Type"
            st.subheader(chart_title)
            metric_col = "price_usd" if is_rent_analytics else "price_per_sqm"
            type_metric = df_analytics.groupby("property_type")[metric_col].median().dropna().reset_index()
            type_metric = type_metric.sort_values(by=metric_col, ascending=False)
            st.bar_chart(type_metric.set_index("property_type"))

        with a_col2:
            st.subheader(f"{target_ltype} Listings by Portal Source")
            src_counts = df_analytics["source"].value_counts().reset_index()
            src_counts.columns = ["source", "count"]
            st.bar_chart(src_counts.set_index("source"))

        st.markdown("---")
        dist_title = "Phnom Penh Districts: Median Rental Rate ($/m²/mo)" if is_rent_analytics else "Phnom Penh Districts: Median Purchase $/m²"
        st.subheader(dist_title)
        pp_dist = df_analytics[df_analytics["province"].str.contains("Phnom", na=False)]
        if not pp_dist.empty and pp_dist["district"].str.len().gt(0).any():
            dist_pp = pp_dist.groupby("district")["price_per_sqm"].median().dropna().reset_index()
            dist_pp = dist_pp.sort_values(by="price_per_sqm", ascending=False).head(15)
            st.bar_chart(dist_pp.set_index("district"))


# ==============================================================================
# WORKSPACE 8: ⚙️ DATABASE & SYSTEM CONSOLE (NEW PORTAL MODULE)
# ==============================================================================
def render_database_console():
    render_tab_hero(
        icon_class="fa-solid fa-server",
        title="Database & System Console",
        subtitle="Manage SQLite storage, execute integrity optimization, clean duplicates, and audit scraper logs.",
        badge_text="Enterprise Administration",
        color="#4f46e5",
        bg_gradient="rgba(79, 70, 229, 0.08)"
    )

    telem = get_database_telemetry()

    # Diagnostics Row
    d1, d2, d3, d4 = st.columns(4)
    with d1:
        render_html(f"""
        <div class="metric-box" style="border-left: 3px solid #00d4ff;">
            <div class="metric-label">Database File Size</div>
            <div class="metric-value" style="color:#f1f5f9;">{telem['file_size_mb']} MB</div>
            <div style="font-size:11.5px; color:#64748b; margin-top:4px;">SQLite 3 Storage File</div>
        </div>
        """)
    with d2:
        render_html(f"""
        <div class="metric-box" style="border-left: 3px solid #00ff88;">
            <div class="metric-label">Total Ingested Listings</div>
            <div class="metric-value" style="color:#00ff88;">{telem['total_properties']:,}</div>
            <div style="font-size:11.5px; color:#64748b; margin-top:4px;">Across 5 Portals</div>
        </div>
        """)
    with d3:
        render_html(f"""
        <div class="metric-box" style="border-left: 3px solid #d97706;">
            <div class="metric-label">Scraper Run Logs</div>
            <div class="metric-value" style="color:#d97706;">{telem['total_logs']:,}</div>
            <div style="font-size:11.5px; color:#64748b; margin-top:4px;">Historical Crawl Audits</div>
        </div>
        """)
    with d4:
        render_html(f"""
        <div class="metric-box" style="border-left: 3px solid #a855f7;">
            <div class="metric-label">Duplicate URL Check</div>
            <div class="metric-value" style="color:#7c3aed;">{telem['duplicate_urls']}</div>
            <div style="font-size:11.5px; color:#00ff88; margin-top:4px;">Unique Index Validated</div>
        </div>
        """)

    st.markdown("<div style='margin-top:24px;'></div>", unsafe_allow_html=True)

    # Database Maintenance Deck
    m_col1, m_col2 = st.columns(2)
    with m_col1:
        st.markdown("<div class='glass-panel'>", unsafe_allow_html=True)
        st.markdown("<div style='font-size:14px; font-weight:700; color:#f1f5f9; margin-bottom:10px;'><i class='fa-solid fa-broom' style='color:#00d4ff; margin-right:8px;'></i>Optimize & Vacuum Storage</div>", unsafe_allow_html=True)
        st.write("Reclaims unused disk space, defragments SQLite B-tree pages, and recomputes statistical indexes for query speed.")
        
        if st.button("🧹 Run Database Vacuum & Optimize", use_container_width=True, type="primary"):
            with st.spinner("Vacuuming database and rebuilding indexes..."):
                res = vacuum_database()
            if res["success"]:
                st.success(f"✅ Vacuum completed! Size before: **{res['size_before_mb']} MB** &rarr; Size after: **{res['size_after_mb']} MB** (Reclaimed **{res['reclaimed_mb']} MB**).")
            else:
                st.error(f"Error during vacuum: {res['error']}")
        st.markdown("</div>", unsafe_allow_html=True)

    with m_col2:
        st.markdown("<div class='glass-panel'>", unsafe_allow_html=True)
        st.markdown("<div style='font-size:14px; font-weight:700; color:#f1f5f9; margin-bottom:10px;'><i class='fa-solid fa-clone' style='color:#00ff88; margin-right:8px;'></i>Deduplicate Property Records</div>", unsafe_allow_html=True)
        st.write("Scans for any duplicate listing URLs that may exist from concurrent scraper workers and removes older redundant entries.")
        
        if st.button("🗑️ Scan & Deduplicate Records", use_container_width=True):
            with st.spinner("Scanning for redundant listings..."):
                removed = cleanup_duplicate_properties()
            if removed > 0:
                st.success(f"✅ Deduplication finished! Removed **{removed}** duplicate entries.")
            else:
                st.info("✨ Clean database! No duplicate property records were found.")
        st.markdown("</div>", unsafe_allow_html=True)

    # Scraper Execution Audit Browser
    st.markdown("<div style='margin-top:20px;'></div>", unsafe_allow_html=True)
    st.markdown("<div style='font-size:14px; font-weight:700; color:#f1f5f9; margin-bottom:12px;'><i class='fa-solid fa-clock-rotate-left' style='color:#a855f7; margin-right:8px;'></i>Comprehensive Scraper Execution Audit Log</div>", unsafe_allow_html=True)

    filter_src = st.selectbox("Filter Logs by Portal Source", ["All"] + list(WEBSITES.keys()), format_func=lambda x: WEBSITES.get(x, {}).get("name", x))
    full_logs_df = get_scrape_logs(limit=100, source=filter_src)
    
    if not full_logs_df.empty:
        st.dataframe(
            full_logs_df,
            use_container_width=True,
            column_config={
                "id": "Log ID",
                "source": "Portal Source",
                "status": "Status",
                "items_found": st.column_config.NumberColumn("Found", format="%d"),
                "items_saved": st.column_config.NumberColumn("Saved", format="%d"),
                "started_at": "Start Time",
                "finished_at": "End Time",
                "error_message": "Error Message"
            }
        )
        csv_logs = full_logs_df.to_csv(index=False).encode("utf-8")
        st.download_button(
            label="📥 Download Audit Logs (CSV)",
            data=csv_logs,
            file_name="scraper_audit_logs.csv",
            mime="text/csv"
        )
    else:
        st.info("No logs found for this filter.")


# ==============================================================================
# MAIN ROUTING CONTROLLER
# ==============================================================================
if active_workspace == "🌐 Command Center":
    render_command_center()
elif active_workspace == "🕷️ Scraping Operations":
    render_scraper_hub()
elif active_workspace == "📋 MLS Property Explorer":
    render_property_explorer()
elif active_workspace == "⚖️ CMA Valuation Studio":
    render_cma_studio()
elif active_workspace == "💎 Deal Finder & Screener":
    render_deal_finder()
elif active_workspace == "📐 Land Valuation Estimator":
    render_land_estimator()
elif active_workspace == "📊 Market Intelligence Hub":
    render_market_analytics()
elif active_workspace == "⚙️ Database & System Console":
    render_database_console()
else:
    render_command_center()
