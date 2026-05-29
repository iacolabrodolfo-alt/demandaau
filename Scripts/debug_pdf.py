import sys
from pypdf import PdfReader

if len(sys.argv) < 2:
    print("Uso: python debug_pdf.py ruta_del_pdf")
    sys.exit(1)

ruta = sys.argv[1]
reader = PdfReader(ruta)

for i, pagina in enumerate(reader.pages):
    texto = pagina.extract_text()
    print(f"\n========== PÁGINA {i+1} ==========\n")
    print(texto)
    print("\n================================\n")