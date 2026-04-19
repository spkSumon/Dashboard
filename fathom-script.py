import requests

# Vul hier je eigen gegevens in
API_KEY = "3377699761000144|ZhxxOuBDflYHpimJd7QtnAaf5TbSeu9c3mARhsFo"
SITE_ID = "WGXUYRJQ"

# Headers die bij elk request meegaan
HEADERS = {"Authorization": f"Bearer {API_KEY}"}


def site_info():
    """Haal de basisinfo van je site op."""
    url = f"https://api.usefathom.com/v1/sites/{SITE_ID}"
    response = requests.get(url, headers=HEADERS)
    data = response.json()
    print(f"Site naam: {data['name']}")
    print(f"Aangemaakt op: {data['created_at']}")


def bezoeken_deze_maand():
    """Tel het aantal bezoekers en pageviews van deze maand."""
    url = "https://api.usefathom.com/v1/aggregations"
    params = {
        "entity": "pageview",
        "entity_id": SITE_ID,
        "aggregates": "visits,pageviews",
        "date_from": "2026-03-01",
        "date_to": "2026-03-24",
    }
    response = requests.get(url, headers=HEADERS, params=params)
    data = response.json()
    print(f"Bezoekers: {data[0]['visits']}")
    print(f"Pageviews: {data[0]['pageviews']}")


def populaire_paginas():
    """Toon de top 5 meest bezochte pagina's."""
    url = "https://api.usefathom.com/v1/aggregations"
    params = {
        "entity": "pageview",
        "entity_id": SITE_ID,
        "aggregates": "visits,pageviews",
        "field_grouping": "pathname",
        "sort_by": "pageviews:desc",
        "limit": 5,
        "date_from": "2026-03-01",
        "date_to": "2026-03-24",
    }
    response = requests.get(url, headers=HEADERS, params=params)
    data = response.json()
    for i, pagina in enumerate(data, 1):
        print(f"{i}. {pagina['pathname']} — {pagina['pageviews']} views")

def sites_bezoeken():
    """Toon de top 50 verwijzende sites."""
    url = "https://api.usefathom.com/v1/aggregations"
    params = {
        "entity": "pageview",
        "aggregates": "visits,pageviews",
        "entity_id": SITE_ID,
        "field_grouping": "referrer_hostname",
        "sort_by": "visits:desc",
        "date_from": "2026-03-01 00:00:00",
        "date_to": "2026-03-24 23:59:59",
        "limit": 50,
      
    }

    response = requests.get(url, headers=HEADERS, params=params, timeout=30)
    
    print("Body:", response.text, "pageviews/visits:", response.status_code)

print("\n=== VERWIJZENDE SITES ===")
sites_bezoeken()
# --- Voer de functies uit ---
if __name__ == "__main__":
    print("=== SITE INFO ===")
    site_info()
    print("\n=== BEZOEKEN DEZE MAAND ===")
    bezoeken_deze_maand()
    print("\n=== POPULAIRE PAGINA'S ===")
    populaire_paginas()


