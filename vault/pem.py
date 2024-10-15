import base64
from Crypto.PublicKey import RSA

# Base64url decode the modulus (n) and exponent (e)
n = "ks9o6uwMh-xCNB5D5YIVo6xzU5JswfwlNF_DkU03cAJHceU9LxlMxP6dNHX6jtWiPYOwTVO-OnYCLGkt9uHqHJDh6zdSG3sONahSHSC1nGUX9MY8sLnFL_mvukNqCA6jOqbl8tLaPf8KDyAC3PSqhl25C3ud-pAGek_D4etqbX_XVHnZVQoQrdoOuBIqPS7N2oafccWAoTx33xyEQyYBk4082cr5DqX-Mu4gyYD9ZZ6KabLUnxWHp4GVDzDE3hodPOEFRIjf5VTzauFRe8WFRRMVDwtzkGs8NxOInV80cHwiP5WzpCdnCeMXOcDOn3rBp_YtVB_5H5H4Jv0p5vzBWw"
e = "AQAB"

# Convert base64url to base64
n = base64.urlsafe_b64decode(n + '==')
e = base64.urlsafe_b64decode(e + '==')

# Create RSA key
rsa_key = RSA.construct((int.from_bytes(n, 'big'), int.from_bytes(e, 'big')))
pem_key = rsa_key.exportKey()

print(pem_key.decode())
