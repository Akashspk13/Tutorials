import re
import os
import geoip2.database
from pymongo import MongoClient

access_log_file = "/usr/local/nginx/logs/access.log"
error_log_file = "/usr/local/nginx/logs/error.log"
mongo_uri = "mongodb://localhost:27017/"
mongo_database = "transit_app"
access_collection = "access_logs"
error_collection = "error_logs"

client = MongoClient(mongo_uri)
db = client[mongo_database]
access_logs = db[access_collection]
error_logs = db[error_collection]

def get_host_location(ip):
    with geoip2.database.Reader('/usr/share/GeoIP/GeoLite2-City.mmdb') as reader:
        response = reader.city(ip)
        city_name = response.city.name if response.city.name else ""
        subdivision_name = response.subdivisions.most_specific.name if response.subdivisions.most_specific.name else ""
        country_name = response.country.name if response.country.name else ""
        return f"{city_name}, {subdivision_name}, {country_name}"

def insert_log_line(line, log_type):
    pattern = r'^(\S+) (\S+) (\S+) \[([\w:/]+\s[+\-]\d{4})\] "(\S+) (\S+)\s*(\S+)?\s*" (\d{3}) (\d+) "(\S+)" "(.*)"'
    match = re.match(pattern, line)
    if match is not None:
        ip = match.group(1)
        host_location = get_host_location(ip)
        if host_location is not None:
            log_data = {
                "ip_address": ip,
                "timestamp": match.group(4),
                "method": match.group(5),
                "url": match.group(6),
                "http_version": match.group(7),
                "response_code": match.group(8),
                "content_size": match.group(9),
                "referrer": match.group(10),
                "user_agent": match.group(11),
                "host_location": host_location,
                "log_type": log_type
            }
            if log_type == "access":
                access_logs.insert_many([log_data], ordered=False)
            elif log_type == "error":
                error_logs.insert_many([log_data], ordered=False)

def process_logs(log_file, log_type):
    with open(log_file, "r") as file:
        for line in reversed(list(file)):
            insert_log_line(line, log_type)

def main():
    process_logs(access_log_file, "access")
    process_logs(error_log_file, "error")
    access_logs = db[access_collection].find().sort("timestamp", -1)
    error_logs = db[error_collection].find().sort("timestamp", -1)
    print("Access Logs:")
    for log in access_logs:
        print(log)
    print("Error Logs:")
    for log in error_logs:
        print(log)

if __name__ == "__main__":
    main()
