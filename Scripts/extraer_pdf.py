import sys
import json
import re
from pypdf import PdfReader

def limpiar_texto(texto):
    # Reemplazar caracteres extraños comunes en estos PDFs
    sustituciones = {
        'tô': '', 'ŠTA': 'ESTA', 'ß': '', '&': 'Y', '@': '', '#': '', '°': ' ',
        'ê': 'e', 'ë': 'e', 'á': 'a', 'é': 'e', 'í': 'i', 'ó': 'o', 'ú': 'u',
        'ñ': 'n', 'ü': 'u', 'â': 'a', 'ã': 'a', 'ç': 'c', 'ì': 'i', 'ò': 'o',
        'Ø': 'O', 'ø': 'o', 'ð': 'd', 'þ': 'th', 'æ': 'ae', 'œ': 'oe',
        '\\x': ' ', '[^\\x20-\\x7E]': ' '
    }
    for k, v in sustituciones.items():
        texto = texto.replace(k, v)
    # Eliminar cualquier carácter que no sea letra, número, espacio, punto, coma, guión, $, :
    texto = re.sub(r'[^\w\s\.\,\-\$\:\°\Ñ\ñ\Á\á\É\é\Í\í\Ó\ó\Ú\ú]', ' ', texto)
    # Reducir múltiples espacios
    texto = re.sub(r'\s+', ' ', texto)
    return texto.strip()

def extraer_datos_pdf(ruta):
    reader = PdfReader(ruta)
    texto_completo = ""
    for pagina in reader.pages:
        txt = pagina.extract_text()
        if txt:
            texto_completo += txt + "\n"
    
    if not texto_completo:
        return {"error": "No se pudo extraer texto"}
    
    texto_limpio = limpiar_texto(texto_completo)
    
    # --- Tipo (prioridad a "A LA VISTA") ---
    tipo = "SIMPLE"
    if re.search(r'PAGAR[EÉ]\s+A\s+LA\s+VISTA', texto_limpio, re.IGNORECASE):
        tipo = "A LA VISTA"
    elif re.search(r'PAGAR[EÉ]\s+EN\s+CUOTAS', texto_limpio, re.IGNORECASE):
        tipo = "EN CUOTAS"
    elif re.search(r'CONTRATO', texto_limpio, re.IGNORECASE):
        tipo = "CONTRATO"
    
    # --- Número de pagaré ---
    num_pagare = None
    m = re.search(r'N[\s°º]*(\d+)', texto_limpio)
    if m:
        num_pagare = m.group(1)
    
    # --- Monto ---
    monto = None
    m = re.search(r'[\$\S]*\s*(\d[\d\.,]*)', texto_limpio)
    if m:
        monto = m.group(1).replace('.', '').replace(',', '')
    
    # --- Domicilio y comuna (limpiando basura) ---
    domicilio = None
    comuna = None
    # Buscar el patrón "DOMICILIO EN CALLE ... COMUNA DE ..."
    patron = r'DOMICILIO EN CALLE\s+(.+?)\s+COMUNA DE\s+([A-ZÑÁÉÍÓÚ\s]+)'
    m = re.search(patron, texto_limpio, re.IGNORECASE)
    if m:
        domicilio = re.sub(r'[^A-ZÑÁÉÍÓÚ\s\.\,\-\d]', '', m.group(1)).strip()
        comuna = re.sub(r'[^A-ZÑÁÉÍÓÚ\s]', '', m.group(2)).strip()
        # Limpiar sobras como "DE ESTA CIUDADY..."
        comuna = comuna.split('DE')[0].strip()
    else:
        # Fallback: buscar "DOMICILIO:" después de NOMBRE
        partes = texto_limpio.split('DOMICILIO:')
        if len(partes) > 1:
            raw = partes[1].split('R.U.T.')[0].strip()
            domicilio = re.sub(r'[^A-ZÑÁÉÍÓÚ\s\.\,\-\d]', '', raw)
    
    # --- Nombre del deudor ---
    nombre_deudor = None
    m = re.search(r'NOMBRE:\s*([A-ZÑÁÉÍÓÚ\s]+?)(?=\s*DOMICILIO|R\.U\.T\.|$)', texto_limpio, re.IGNORECASE)
    if m:
        nombre_deudor = re.sub(r'\s+', ' ', m.group(1).strip())
    
    # --- RUT del deudor ---
    rut_deudor = None
    m = re.search(r'R\.U\.T\.:\s*(\d{7,8}-\d)', texto_limpio)
    if m:
        rut_deudor = m.group(1)
    
    # --- Repertorio y fecha (búsqueda en texto sucio) ---
    repertorio = None
    fecha_repertorio = None
    # Buscar patrones como "RepertorioIf34124095" -> extraer 3412-2005
    patron_rep = r'Repertorio\w*(\d{4,5}-\d{4})'
    m = re.search(patron_rep, texto_limpio, re.IGNORECASE)
    if m:
        repertorio = m.group(1)
    else:
        m = re.search(r'(\d{4}-\d{4})', texto_limpio)
        if m:
            repertorio = m.group(1)
    
    # Buscar fecha (aunque tenga errores: "01 derebæro dekuwl" -> intentamos normalizar)
    patron_fecha = r'(\d{1,2})\s+de\s+(\w+)\s+de\s+(\d{4})'
    m = re.search(patron_fecha, texto_limpio, re.IGNORECASE)
    if m:
        dia = m.group(1)
        mes_raw = m.group(2).lower()
        # Normalizar nombres de meses con errores
        meses = {
            'enero': '01', 'febrero': '02', 'marzo': '03', 'abril': '04',
            'mayo': '05', 'junio': '06', 'julio': '07', 'agosto': '08',
            'septiembre': '09', 'octubre': '10', 'noviembre': '11', 'diciembre': '12',
            'rebæro': '02', 'rebero': '02', 'febrero': '02', 'dekuwl': None
        }
        mes_num = meses.get(mes_raw, '01')
        if mes_num:
            fecha_repertorio = f"{dia} de {m.group(2).capitalize()} de {m.group(3)}"
    
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
    resultado = extraer_datos_pdf(sys.argv[1])
    print(json.dumps(resultado, ensure_ascii=False))