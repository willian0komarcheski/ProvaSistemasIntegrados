#!/bin/bash

API_URL="http://localhost:5000"

echo "Testing Status Endpoint..."
curl -X GET "${API_URL}/status"
echo -e "\n"

echo "Testing Events List Endpoint..."
curl -X GET "${API_URL}/events"
echo -e "\n"

echo "Testing Event Creation Endpoint..."
curl -X POST "${API_URL}/event" \
  -H "Content-Type: application/json" \
  -d '{
    "message": "Test alert event",
    "source": "test-script",
    "severity": "high",
    "sensorId": "sensor-123"
  }'
echo -e "\n"

echo "Getting updated events list..."
curl -X GET "${API_URL}/events"
echo -e "\n"