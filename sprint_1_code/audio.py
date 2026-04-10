import os


directory = "/www/wwwroot/default/Video/ITIES_audio/IELTS_19_Listening/"

def rename_ielts_files():

    if not os.path.exists(directory):
        print(f"Error: Can't find directory {directory}")
        return

    count = 0
   
    for filename in os.listdir(directory):
      
        if " " in filename:
           
            new_name = filename.replace(" ", "_")
            
           
            old_path = os.path.join(directory, filename)
            new_path = os.path.join(directory, new_name)
            
            
            try:
                os.rename(old_path, new_path)
                print(f"Successful rename: '{filename}' -> '{new_name}'")
                count += 1
            except Exception as e:
                print(f"Renaming '{filename}' error: {e}")
                
    print(f"\nProcessing completed! A total of {count} files have been modified.")

if __name__ == "__main__":
    rename_ielts_files()