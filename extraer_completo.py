import sys
import json
import re
import fitz

def limpiar(texto):
    # Reemplazar caracteres no deseados
    texto = re.sub(r'[^\x20-\x7E\xA0-\xFF\n]', ' ', texto)
    texto = re.sub(r'\s+', ' ', texto)
    return texto.strip()

def extraer_datos_pdf(ruta_pdf):
    doc = fitz.open(ruta_pdf)
    texto_completo = ""
    for pagina in doc:
        texto = pagina.get_text()
        texto_completo += texto + "\n"
    doc.close()
    
    texto_limpio = limpiar(texto_completo)
    
    # Tipo
    tipo = "SIMPLE"
    if re.search(r'PAGARÉ\s+A\s+LA\s+VISTA', texto_limpio, re.IGNORECASE):
        tipo = "A LA VISTA"
    elif re.search(r'PAGARÉ\s+EN\s+CUOTAS', texto_limpio, re.IGNORECASE):
        tipo = "EN CUOTAS"
    elif re.search(r'CONTRATO', texto_limpio, re.IGNORECASE):
        tipo = "CONTRATO"
    
    # Número
    num_pagare = None
    m = re.search(r'N[°º]\s*(\d+)', texto_limpio)
    if m:
        num_pagare = m.group(1)
    
    # Monto
    monto = None
    m = re.search(r'\$\s*([\d\.,]+)', texto_limpio)
    if m:
        monto = m.group(1).replace('.', '').replace(',', '')
    
    # Domicilio y comuna
    domicilio = None
    comuna = None
    # Buscar "DOMICILIO EN CALLE ... COMUNA DE ..."
    m = re.search(r'DOMICILIO EN CALLE\s+(.+?)\s+COMUNA DE\s+([A-ZÑÁÉÍÓÚ\s]+)', texto_limpio, re.IGNORECASE)
    if m:
        domicilio = m.group(1).strip()
        comuna = m.group(2).strip()
    else:
        # Fallback: después de "DOMICILIO:"
        m = re.search(r'DOMICILIO:\s*([^R]+?)(?=R\.U\.T\.|$)', texto_limpio, re.IGNORECASE)
        if m:
            domicilio = m.group(1).strip()
    
    # Nombre deudor
    nombre_deudor = None
    m = re.search(r'NOMBRE:\s*([A-ZÑÁÉÍÓÚ\s]+?)(?=\s*DOMICILIO|$)', texto_limpio, re.IGNORECASE)
    if m:
        nombre_deudor = re.sub(r'\s+', ' ', m.group(1).strip())
    
    # RUT
    rut_deudor = None
    m = re.search(r'R\.U\.T\.:\s*(\d{7,8}-\d)', texto_limpio)
    if m:
        rut_deudor = m.group(1)
    
    # Repertorio y fecha
    repertorio = None
    fecha_repertorio = None
    m = re.search(r'Repertorio\s*N[°º]?\s*(\d+[-]\d+).*?fecha\s*(\d{1,2}\s+de\s+\w+\s+de\s+\d{4})', texto_limpio, re.IGNORECASE)
    if m:
        repertorio = m.group(1)
        fecha_repertorio = m.group(2)
    else:
        # Si no, buscar solo el número
        m = re.search(r'Repertorio\s*N[°º]?\s*(\d+[-]\d+)', texto_limpio, re.IGNORECASE)
        if m:
            repertorio = m.group(1)
    
    return {
        "tipo": tipo,
        "num_pagare": num_pagare,
        "monto": monto,
        "domicilio": domicilio,
        "comuna": comuna,
        "nombre_deudor": nombre_deudor,
        "rut_deudor": rut_deudor,
        "repertorio": repertorio,
        "fecha_repertorio": fecha_repertorio
    }

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"error": "Debe proporcionar ruta del PDF"}))
        sys.exit(1)
    ruta = sys.argv[1]
    try:
        datos = extraer_datos_pdf(ruta)
        print(json.dumps(datos, ensure_ascii=False))
    except Exception as e:
        print(json.dumps({"error": str(e)}))