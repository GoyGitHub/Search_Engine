import pandas as pd
import os
import random
from faker import Faker

fake = Faker("en_US")

os.makedirs("excel_files", exist_ok=True)

# =========================
# SETTINGS
# =========================
NUM_RECORDS = 1200  # 1000+ data per file

# =========================
# 1. CUSTOMERS DATA
# =========================
customers = []

for i in range(1, NUM_RECORDS + 1):
    customers.append({
        "id": i,
        "name": fake.name(),
        "email": fake.email(),
        "phone": fake.phone_number(),
        "city": fake.city(),
        "address": fake.address()
    })

df_customers = pd.DataFrame(customers)
df_customers.to_excel("excel_files/customers.xlsx", index=False)

# =========================
# 2. PRODUCTS DATA
# =========================
product_names = [
    "Shampoo", "Conditioner", "Hair Gel", "Hair Wax", "Pomade",
    "Hair Dye", "Comb", "Scissors", "Hair Dryer", "Towel",
    "Clippers", "Beard Oil", "Styling Cream", "Hair Spray"
]

categories = ["Hair Care", "Styling", "Tools", "Equipment", "Accessories"]

products = []

for i in range(1, NUM_RECORDS + 1):
    products.append({
        "product_id": 1000 + i,
        "product_name": random.choice(product_names),
        "price": round(random.uniform(50, 1500), 2),
        "category": random.choice(categories),
        "stock": random.randint(1, 200),
        "supplier": fake.company()
    })

df_products = pd.DataFrame(products)
df_products.to_excel("excel_files/products.xlsx", index=False)

# =========================
# 3. APPOINTMENTS DATA
# =========================
services = ["Haircut", "Manicure", "Pedicure", "Hair Treatment", "Hair Coloring", "Shave"]

appointments = []

for i in range(1, NUM_RECORDS + 1):
    appointments.append({
        "appointment_id": i,
        "customer_name": fake.name(),
        "service": random.choice(services),
        "date": fake.date_between(start_date="-1y", end_date="today"),
        "time": f"{random.randint(9, 18)}:{random.choice(['00','15','30','45'])}",
        "status": random.choice(["Completed", "Pending", "Cancelled"])
    })

df_appointments = pd.DataFrame(appointments)
df_appointments.to_excel("excel_files/appointments.xlsx", index=False)

# =========================
# DONE
# =========================
print("✅ 1000+ realistic Excel records generated successfully!")
print("Files saved in /excel_files folder")