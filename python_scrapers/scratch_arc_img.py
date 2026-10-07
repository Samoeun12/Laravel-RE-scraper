import httpx
import re

js = httpx.get('https://arc.com.kh/assets/index-CHQJRbXu.js').text
# Find image base or download endpoint
download_matches = set(re.findall(r'https?://[a-zA-Z0-9.-]+(?:/[a-zA-Z0-9_.-]+)*(?:/files/|/image|/img|/media)[a-zA-Z0-9_.-]*', js))
print("Download endpoints:", download_matches)

for m in re.finditer(r'property_thumbnail', js):
    start = max(0, m.start() - 60)
    end = min(len(js), m.end() + 100)
    print("Context:", js[start:end])
    break
