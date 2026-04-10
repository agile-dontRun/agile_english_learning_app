import csv
import re
import pymysql


DB_HOST = "localhost"
DB_PORT = 3306  
DB_USER = "Zll"
DB_PASSWORD = "Xz83MjpM4fm8mEd5" 
DB_NAME = "english_learning_app"

CSV_FILE = "ecdict.csv"
BATCH_SIZE = 5000  

def clean_text(value, max_len=None):
    if value is None: return None
    text = str(value).strip()
    if text.lower() in ("", "nan", "none", "null"): return None
    text = text.replace("\\n", " ").replace("\r", " ").replace("\n", " ")
    text = re.sub(r"\s{2,}", " ", text).strip()
    if max_len and len(text) > max_len: text = text[:max_len]
    return text if text != "" else None


conn = pymysql.connect(
    host=DB_HOST,
    port=DB_PORT,
    user=DB_USER,
    password=DB_PASSWORD,
    database=DB_NAME,
    charset="utf8mb4"
)
cursor = conn.cursor()




data_buffer = []
total_count = 0

print("Loading...")

try:
    with open(CSV_FILE, "r", encoding="utf-8-sig") as f:
        reader = csv.DictReader(f)
        for row in reader:
            word = clean_text(row.get("word"), 100)
            if not word: continue
            
           
            item = (
                word,
                clean_text(row.get("translation")),
                clean_text(row.get("phonetic"), 100),
                clean_text(row.get("pos"), 50),
                None, # example_sentence
                clean_text(row.get("definition")),
                clean_text(row.get("audio")) or clean_text(row.get("audio_us")) or clean_text(row.get("audio_uk"))
            )
            data_buffer.append(item)

            
            if len(data_buffer) >= BATCH_SIZE:
                cursor.executemany(sql, data_buffer)
                conn.commit()  
                total_count += len(data_buffer)
                print(f"Have load {total_count} count...")
                data_buffer = []

        
        if data_buffer:
            cursor.executemany(sql, data_buffer)
            conn.commit()
            total_count += len(data_buffer)

    print(f"Load successfully！Total {total_count} count。")

except Exception as e:
    conn.rollback()
    print(f"❌Error{e}")
finally:
    cursor.close()
    conn.close()