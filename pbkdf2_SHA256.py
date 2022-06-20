import sys
from passlib.hash import ldap_pbkdf2_sha256

print(ldap_pbkdf2_sha256.hash(sys.argv[1],rounds=10000))