import pandas as pd
import os
import random
from faker import Faker

fake = Faker("en_US")
os.makedirs("excel_files", exist_ok=True)

NUM_RECORDS = 1000

def save_excel(filename, rows):
    df = pd.DataFrame(rows)
    df.to_excel(f"excel_files/{filename}.xlsx", index=False)

# 1 Customers
save_excel("customers", [
    {
        "id": i,
        "name": fake.name(),
        "email": fake.email(),
        "phone": fake.phone_number()
    } for i in range(1, NUM_RECORDS + 1)
])

# 2 Products
save_excel("products", [
    {
        "id": i,
        "name": fake.word().title(),
        "price": round(random.uniform(50, 5000), 2),
        "stock": random.randint(1, 500)
    } for i in range(1, NUM_RECORDS + 1)
])

# 3 Orders
save_excel("orders", [
    {
        "id": i,
        "customer_id": random.randint(1, NUM_RECORDS),
        "total": round(random.uniform(100, 10000), 2)
    } for i in range(1, NUM_RECORDS + 1)
])

# 4 Payments
save_excel("payments", [
    {
        "id": i,
        "order_id": random.randint(1, NUM_RECORDS),
        "method": random.choice(["Cash", "Card", "GCash"])
    } for i in range(1, NUM_RECORDS + 1)
])

# Continue same pattern...
tables = [
    "employees", "suppliers", "inventory", "reviews", "appointments",
    "services", "categories", "branches", "expenses", "attendance",
    "returns", "shipments", "coupons", "logs", "notifications",
    "messages", "tickets", "subscriptions", "analytics"
]

for table in tables:
    save_excel(table, [
        {
            "id": i,
            "name": fake.word().title(),
            "created_at": fake.date_this_year()
        } for i in range(1, NUM_RECORDS + 1)
    ])

print("20+ Excel files created successfully.")