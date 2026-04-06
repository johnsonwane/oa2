# -*- coding: utf-8 -*-
import os
import re

os.chdir(r'D:\GitHub\oa2')

# Pattern 1: multiline format (ends with .sidebar-overlay{display:none!important;})
pattern1 = r'html\[data-embedded="1"\] \.menu-toggle,html\[data-embedded="1"\] \.sidebar-overlay\{display:none!important;\}'
replacement1 = 'html[data-embedded="1"] .menu-toggle,html[data-embedded="1"] .sidebar-overlay{display:none!important;}html[data-embedded="1"] .oa-header h1{display:none!important;}'

# Pattern 2: compressed single line format
pattern2 = r'html\[data-embedded="1"\] \.sidebar-overlay\{display:none!important;\}'
replacement2 = 'html[data-embedded="1"] .sidebar-overlay{display:none!important;}html[data-embedded="1"] .oa-header h1{display:none!important;}'

count = 0
for fname in os.listdir('.'):
    if fname.endswith('.html') and fname not in ['index.html', 'login.html']:
        fpath = os.path.join('.', fname)
        with open(fpath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        new_content = content
        if re.search(pattern1, content):
            new_content = re.sub(pattern1, replacement1, content)
            print(f'Updated (multiline): {fname}')
            count += 1
        elif re.search(pattern2, content):
            new_content = re.sub(pattern2, replacement2, content)
            print(f'Updated (single): {fname}')
            count += 1
        
        if new_content != content:
            with open(fpath, 'w', encoding='utf-8') as f:
                f.write(new_content)

print(f'Total updated: {count} files')
