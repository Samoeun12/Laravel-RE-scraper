# 🏢 Cambodia Real Estate Scraper & Valuation Tool

An AI-ready Python scraping & valuation platform with a modern Streamlit Web UI to scrape, analyze, and estimate properties across Cambodia's 5 premier real estate portals:

1. [realestate.com.kh](https://www.realestate.com.kh/)
2. [khmer24.com](https://www.khmer24.com/en/)
3. [harbor-property.com](https://www.harbor-property.com/en/)
4. [arc.com.kh](https://arc.com.kh/) (Asia Real Estate Cambodia)
5. [cambodia-real-estate.com](https://cambodia-real-estate.com/) (Century 21 Cambodia)

---

## 🌟 Key Features

- **🕷️ Multi-Portal Scraping Hub**:
  - Scrape all 5 portals or selectively choose targets.
  - Direct REST APIs for **ARC**, **Harbor Property**, and **Cambodia Real Estate (C21)** (ultra-fast, sub-second queries).
  - Schema & HTTP parsing for **Realestate.com.kh**.
  - Headless Playwright automation with anti-detection for **Khmer24**.
  - Live progress bars and status updates.

- **📋 Property Listings Explorer**:
  - Search and filter by portal, property type (Land, Condo, Villa, etc.), province, district, price range, area (m²), and $/m².
  - Two display views: **Interactive Grid** with sorting and **Card Showcase** with images.
  - One-click export to **CSV** and **Excel (.xlsx)**.

- **⚖️ Comparable Market Analysis (CMA)**:
  - Generate an automated CMA report for any property in Cambodia.
  - Computes: Comp count, Min $/m², 25th percentile, Median $/m², Average $/m², 75th percentile, Max $/m².
  - Outputs 3-tier valuation: Conservative, Fair Market Value, and Premium.
  - Compares asking price against market to detect if a listing is over- or under-priced.
  - Lists top comparable properties with similarity scores.

- **💎 Deal & "Good Property" Finder**:
  - Automatically identifies listings trading at significant discounts (>15% to >35%) relative to their district median $/m².
  - Surfaces urgent sale opportunities ("Urgent Sale", "Below Cost", "Below Market Value").

- **📐 Land Price Estimator**:
  - Dedicated land valuation calculator for developers and investors.
  - Inputs: Province, District, Land Area (m²), Road/Access type (Main Boulevard, Secondary Road, Residential Lane).
  - Displays $/m² benchmarks and total property valuation.
  - District Land Price Index showing Min, Avg, and Max $/m² by district.

- **📊 Market Analytics**:
  - Visual charts showing median $/m² by property type and district.
  - Listing volume breakdown across portals.

---

## 🚀 How to Run

Launch the Streamlit web dashboard with:

```bash
streamlit run app.py
```

The application will open automatically in your browser at `http://localhost:8501`.

---

## 📁 Project Architecture

```
Tool Scrap Data RE/
├── app.py                      # Main Streamlit web application
├── config.py                   # Configuration, portal URLs, categories, tolerances
├── database.py                 # SQLite storage, upsert deduplication, query filters
├── requirements.txt            # Package dependencies
├── scrapers/
│   ├── base_scraper.py         # Standardized BaseScraper and PropertyItem model
│   ├── arc_scraper.py          # arc.com.kh direct REST API scraper
│   ├── harbor_scraper.py       # harbor-property.com direct REST API scraper
│   ├── realestate_scraper.py   # realestate.com.kh HTTP & JSON-LD scraper
│   ├── khmer24_scraper.py      # khmer24.com Playwright scraper
│   ├── cambodia_re_scraper.py  # cambodia-real-estate.com direct REST API scraper
│   └── scraper_manager.py      # Scraper coordinator & progress reporter
└── analytics/
    ├── cma_engine.py           # Comparable Market Analysis (CMA) calculations
    ├── deal_finder.py          # Deal scoring algorithm vs district median
    └── land_estimator.py       # Land price estimation & district index
```
