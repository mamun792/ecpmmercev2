#!/usr/bin/env bash
set -e
rm -f /tmp/cookies.txt /tmp/login.html /tmp/login_headers.txt /tmp/roles_headers.txt /tmp/roles_response.html
curl -s -c /tmp/cookies.txt "http://127.0.0.1:8000/login" -o /tmp/login.html
CSRF=$(grep -oP '<meta name="csrf-token" content="\K[^"]+' /tmp/login.html || true)
echo "CSRF=$CSRF"
curl -s -b /tmp/cookies.txt -c /tmp/cookies.txt -X POST "http://127.0.0.1:8000/login" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  --data-urlencode "email=admin@test.com" \
  --data-urlencode "password=password" \
  --data-urlencode "_token=$CSRF" -D /tmp/login_headers.txt -o /tmp/login_response.html || true
echo "Login headers:"
grep HTTP /tmp/login_headers.txt || true
curl -s -b /tmp/cookies.txt -D /tmp/roles_headers.txt -H "X-Inertia: true" -H "Accept: text/html, application/json" "http://127.0.0.1:8000/admin/roles" -o /tmp/roles_response.html || true
echo "Roles headers:"
grep HTTP /tmp/roles_headers.txt || true
echo "--- body preview ---"
head -c 800 /tmp/roles_response.html | sed -n '1,40p'
