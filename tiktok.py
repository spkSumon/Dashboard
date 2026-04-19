import os
import secrets
from urllib.parse import urlencode

from flask import Flask, redirect, request, jsonify
from dotenv import load_dotenv
import requests

load_dotenv()

app = Flask(__name__)

CLIENT_KEY = os.getenv("awl7mx48gph9l67m")
CLIENT_SECRET = os.getenv("9lba1tLOxjY7GDo9KBPiPqncoyPq4zQo")
REDIRECT_URI = os.getenv("http://localhost:5000/auth/tiktok/callback/")

valid_states = set()

@app.route("/")
def home():
    return """
    <h2>Login demo</h2>
    <a href="http://localhost:5000/auth/tiktok">Continue with TikTok</a>
    """

@app.route("/auth/tiktok")
def auth_tiktok():
    state = secrets.token_hex(16)
    valid_states.add(state)

    params = {
        "client_key": CLIENT_KEY,
        "scope": "user.info.basic,user.info.profile,user.info.stats",
        "response_type": "code",
        "redirect_uri": REDIRECT_URI,
        "state": state
    }

    auth_url = "https://www.tiktok.com/v2/auth/authorize/?" + urlencode(params)
    return redirect(auth_url)

@app.route("/auth/tiktok/callback/")
def tiktok_callback():
    code = request.args.get("code")
    state = request.args.get("state")

    if not code:
        return jsonify({"error": "Missing code"}), 400

    if not state or state not in valid_states:
        return jsonify({"error": "Invalid state"}), 400

    valid_states.remove(state)

    token_response = requests.post(
        "https://open.tiktokapis.com/v2/oauth/token/",
        headers={"Content-Type": "application/x-www-form-urlencoded"},
        data={
            "client_key": CLIENT_KEY,
            "client_secret": CLIENT_SECRET,
            "code": code,
            "grant_type": "authorization_code",
            "redirect_uri": REDIRECT_URI
        },
        timeout=30
    )

    return {
        "status_code": token_response.status_code,
        "body": token_response.json() if token_response.text else {}
    }

if __name__ == "__main__":
    app.run(port=5000, debug=True)