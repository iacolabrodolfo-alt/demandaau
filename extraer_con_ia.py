import sys
import json
from docling.document_converter import DocumentConverter

def extraer_texto_pdf(ruta_pdf):
    converter = DocumentConverter()
    result = converter.convert(ruta_pdf)
    # Extraer todo el texto limpio
    texto_completo = result.document.export_to_text()
    return {"texto": texto_completo}

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"error": "Debe proporcionar la ruta del PDF"}))
        sys.exit(1)
    ruta = sys.argv[1]
    try:
        datos = extraer_texto_pdf(ruta)
        print(json.dumps(datos, ensure_ascii=False))
    except Exception as e:
        print(json.dumps({"error": str(e)}))