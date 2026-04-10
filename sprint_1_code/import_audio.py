import os
import re
import pymysql


DB_CONFIG = {
    'host': '127.0.0.1',
    'port': 3306,
    'user': 'Zll',
    'password': 'Zhulilin1220',
    'database': 'english_learning_app',
    'charset': 'utf8mb4'
}


SCAN_DIR = "/www/wwwroot/default/Video/ITIES_audio"
WEB_ROOT = "/www/wwwroot/default"

def parse_file_info(file_path):
    
    cambridge_match = re.search(r'IELTS_(\d+)', file_path)
    test_match = re.search(r'Test(\d+)', file_path)
    part_match = re.search(r'Part(\d+)', file_path)

    cam_no = int(cambridge_match.group(1)) if cambridge_match else 0
    test_no = int(test_match.group(1)) if test_match else 0
    part_no = int(part_match.group(1)) if part_match else 0
    
    return cam_no, test_no, part_no


def scan_and_import():
    conn = pymysql.connect(**DB_CONFIG)
    cursor = conn.cursor()
    
    count = 0
   
    for root, dirs, files in os.walk(SCAN_DIR):
        for file in files:
            
            if file.lower().endswith(('.mp3', '.wav', '.m4a')):
                full_path = os.path.join(root, file)
                
                
                rel_url = full_path.replace(WEB_ROOT, "")
                
                
                cam_no, test_no, part_no = parse_file_info(full_path)
                title = f"IELTS {cam_no} Test {test_no} Part {part_no}"

               
                sql = """
                INSERT IGNORE INTO ielts_listening_parts 
                (cambridge_no, test_no, part_no, title, audio_url) 
                VALUES (%s, %s, %s, %s, %s)
                """
                cursor.execute(sql, (cam_no, test_no, part_no, title, rel_url))
                count += cursor.rowcount

    conn.commit()
    cursor.close()
    conn.close()
    print(f"Scanning completed! {count} IELTS listening records have been successfully imported/updated."

if __name__ == "__main__":
    scan_and_import()