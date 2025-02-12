import cv2
import numpy as np

def analyze_image(image_file):
    # Bild in Graustufen umwandeln als Beispiel für Verarbeitung
    nparr = np.frombuffer(image_file.read(), np.uint8)
    image = cv2.imdecode(nparr, cv2.IMREAD_GRAYSCALE)
    return f"Bildgröße: {image.shape[0]}x{image.shape[1]} Pixel (Graustufen)"
