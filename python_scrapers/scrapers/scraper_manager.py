"""
Scraper Manager coordinates executing scrapers, progress reporting, and database storage.
"""

from typing import List, Dict, Any, Callable, Optional
from datetime import datetime
import time

from scrapers.arc_scraper import ArcScraper
from scrapers.harbor_scraper import HarborScraper
from scrapers.realestate_scraper import RealestateScraper
from scrapers.khmer24_scraper import Khmer24Scraper
from scrapers.cambodia_re_scraper import CambodiaReScraper
from scrapers.bayon_scraper import BayonScraper
from database import upsert_properties_batch, log_scrape_run

SCRAPER_CLASSES = {
    "arc": ArcScraper,
    "harbor": HarborScraper,
    "realestate": RealestateScraper,
    "khmer24": Khmer24Scraper,
    "cambodia_re": CambodiaReScraper,
    "bayon": BayonScraper
}


class ScraperManager:
    """Manages multi-source scraping jobs with live progress callbacks."""

    def __init__(self):
        self.scrapers = {key: cls() for key, cls in SCRAPER_CLASSES.items()}

    def run_scrapers(
        self,
        target_sources: List[str],
        category: str = "All",
        max_pages: int = 1,
        full_catalog: bool = False,
        progress_callback: Optional[Callable[[float, str], None]] = None
    ) -> Dict[str, Any]:
        """
        Execute scrapers sequentially for selected sources and save results to DB.
        Supports full_catalog mining mode and incremental real-time streaming batch saves.
        """
        results = {}
        total_sources = len(target_sources)
        if total_sources == 0:
            return {"total_found": 0, "total_saved": 0, "by_source": {}}

        total_saved_overall = 0
        total_found_overall = 0

        for idx, source_key in enumerate(target_sources):
            scraper = self.scrapers.get(source_key)
            if not scraper:
                continue

            start_time = datetime.utcnow().isoformat()
            status = "SUCCESS"
            err_msg = None
            found_count = 0
            saved_count = 0

            # Determine page target based on full_catalog mode
            if full_catalog:
                if source_key == "arc":
                    p_target = 999  # Triggers full nationwide map query (~2,560 listings in 1 shot)
                elif source_key == "cambodia_re":
                    p_target = 180  # Triggers auto-probe to fetch all ~176 pages (~8,760 listings)
                elif source_key == "realestate":
                    p_target = 80   # Iterates until catalog end
                elif source_key == "harbor":
                    p_target = 20   # All available catalog sections
                elif source_key == "bayon":
                    p_target = 270  # Iterates through full ~13,450 listings feed
                elif source_key == "khmer24":
                    p_target = max(max_pages, 50)  # Deep feed mine
                else:
                    p_target = max(max_pages, 50)
            else:
                p_target = max_pages

            # Sub-progress reporting
            def sub_callback(curr_p, max_p, message):
                overall_progress = (idx + (curr_p / max_p)) / total_sources
                if progress_callback:
                    progress_callback(min(overall_progress, 0.99), message)

            # Incremental batch save callback (safely commits each page to SQLite)
            def save_batch(batch_items: List[Any]) -> int:
                nonlocal saved_count, total_saved_overall, found_count
                if batch_items:
                    found_count += len(batch_items)
                    dict_items = [it.to_dict() for it in batch_items]
                    cnt = upsert_properties_batch(dict_items)
                    saved_count += cnt
                    total_saved_overall += cnt
                    return cnt
                return 0

            try:
                items = scraper.scrape(
                    category=category,
                    max_pages=p_target,
                    progress_callback=sub_callback,
                    batch_callback=save_batch
                )

                # Fallback save if scraper did not invoke batch_callback
                if saved_count == 0 and items:
                    found_count = len(items)
                    dict_items = [it.to_dict() for it in items]
                    saved_count = upsert_properties_batch(dict_items)
                    total_saved_overall += saved_count
                elif items and found_count == 0:
                    found_count = len(items)

                total_found_overall += found_count

            except Exception as e:
                status = "ERROR"
                err_msg = str(e)
                print(f"[Manager] Error running scraper {source_key}: {e}")

            finish_time = datetime.utcnow().isoformat()
            log_scrape_run(
                source=source_key,
                status=status,
                found=found_count,
                saved=saved_count,
                started_at=start_time,
                finished_at=finish_time,
                error=err_msg
            )

            results[source_key] = {
                "found": found_count,
                "saved": saved_count,
                "status": status,
                "error": err_msg
            }

        if progress_callback:
            progress_callback(1.0, f"Completed! Total {total_found_overall} listings found, {total_saved_overall} saved.")

        return {
            "total_found": total_found_overall,
            "total_saved": total_saved_overall,
            "by_source": results
        }
