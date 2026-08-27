"""
Convert DOCX to PDF using Word COM automation on Windows.
"""
import os
import sys
import win32com.client

DOCX_PATH = os.path.abspath('docs/DOKUMEN_PERANCANGAN_SIGMA-K_v1.0.docx')
PDF_PATH = os.path.abspath('docs/DOKUMEN_PERANCANGAN_SIGMA-K_v1.0.pdf')

def convert():
    print(f"Opening Word to convert:\n  From: {DOCX_PATH}\n  To:   {PDF_PATH}")
    if not os.path.exists(DOCX_PATH):
        print(f"Error: {DOCX_PATH} does not exist!")
        sys.exit(1)

    word = win32com.client.Dispatch('Word.Application')
    word.Visible = False
    word.DisplayAlerts = 0

    try:
        doc = word.Documents.Open(DOCX_PATH)
        # wdFormatPDF = 17
        doc.SaveAs2(PDF_PATH, FileFormat=17)
        doc.Close()
        print(f"Successfully converted to PDF: {PDF_PATH}")
    except Exception as e:
        print(f"Conversion error: {e}")
        sys.exit(1)
    finally:
        word.Quit()

if __name__ == '__main__':
    convert()
