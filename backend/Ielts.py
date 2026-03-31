import os
import re
import mysql.connector


db_config = {
    'host': '127.0.0.1',
    'user': 'Zll',
    'password': 'Xz83MjpM4fm8mEd5', 
    'database': 'english_learning_app'
}


BASE_SCAN_PATH = "/www/wwwroot/default/Video/ITIES_audio/"

URL_PREFIX = "/Video/ITIES_audio/"

def sync_ielts_to_db():
    try:
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor()
        print("Connecting database successfully, loading audio...")

        
        for folder_name in os.listdir(BASE_SCAN_PATH):
            folder_path = os.path.join(BASE_SCAN_PATH, folder_name)
            
            if not os.path.isdir(folder_path):
                continue

           
            cam_match = re.search(r'IELTS_(\d+)', folder_name)
            if not cam_match:
                continue
            cambridge_no = int(cam_match.group(1))

           
            for filename in os.listdir(folder_path):
                if not filename.endswith(".mp3"):
                    continue

                
                file_match = re.search(r'Test(\d+)_Part(\d+)', filename)
                if file_match:
                    test_no = int(file_match.group(1))
                    part_no = int(file_match.group(2))
                    
                    
                    audio_url = f"{URL_PREFIX}{folder_name}/{filename}"
                    title = f"Cambridge {cambridge_no} Test {test_no} Part {part_no}"

                    
                    sql = """
                    INSERT INTO ielts_listening_parts (cambridge_no, test_no, part_no, title, audio_url)
                    VALUES (%s, %s, %s, %s, %s)
                    ON DUPLICATE KEY UPDATE 
                        audio_url = VALUES(audio_url),
                        title = VALUES(title),
                        updated_at = CURRENT_TIMESTAMP;
                    """
                    cursor.execute(sql, (cambridge_no, test_no, part_no, title, audio_url))
                    print(f"Have loaded{title}")

        conn.commit()
        print(f"\nStep completed! Rows affected: {cursor.rowcount}")

    except Exception as e:
        print(f"Error {e}")
    finally:
        if 'conn' in locals() and conn.is_connected():
            cursor.close()
            conn.close()

if __name__ == "__main__":
    sync_ielts_to_db()