import os


directory = "/www/wwwroot/default/Video/ITIES_audio/IELTS_20_Listening/"

def rename_ielts_20():
    if not os.path.exists(directory):
        print(f"Can't find directory: {directory}")
        return

    count = 0
    
    for filename in os.listdir(directory):
        
        if filename.startswith("T") and " " in filename and filename.endswith(".mp3"):
            
            
            new_name = filename.replace("T", "Test").replace(" ", "_").replace("P", "Part")
            
            old_path = os.path.join(directory, filename)
            new_path = os.path.join(directory, new_name)

            try:
                os.rename(old_path, new_path)
                print(f"✅ Successfully renamed: {filename} -> {new_name}")
                count += 1
            except Exception as e:
                print(f"⚠️ Failed to rename {filename}: {e}")

    print(f"\n✨ Processing completed! A total of {count} files have been modified.")

if __name__ == "__main__":
    rename_ielts_20()