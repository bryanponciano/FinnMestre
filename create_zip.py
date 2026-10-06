import zipfile
import os
import shutil

source_dir = 'financeiro'
temp_dir = 'temp_deploy_linux'
zip_filename = 'update_finmestre.zip'

# Remove old zip
if os.path.exists(zip_filename):
    os.remove(zip_filename)

# Create temp dir
if os.path.exists(temp_dir):
    shutil.rmtree(temp_dir)
shutil.copytree(source_dir, temp_dir)

# Remove config/database.php
db_path = os.path.join(temp_dir, 'config', 'database.php')
if os.path.exists(db_path):
    os.remove(db_path)

# Create Zip with Linux-style separators
with zipfile.ZipFile(zip_filename, 'w', zipfile.ZIP_DEFLATED) as zipf:
    for root, dirs, files in os.walk(temp_dir):
        for file in files:
            file_path = os.path.join(root, file)
            arcname = os.path.relpath(file_path, temp_dir)
            arcname = arcname.replace(os.path.sep, '/')  # Force forward slash
            zipf.write(file_path, arcname)

# Cleanup
shutil.rmtree(temp_dir)
print("ZIP criado com sucesso no formato Linux!")
