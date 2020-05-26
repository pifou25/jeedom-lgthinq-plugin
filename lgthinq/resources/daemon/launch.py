#!/usr/bin/env python3
"""
This script is used to launch the wideqServer with check about the right python version
"""
import sys

if sys.version_info > (3, 6):
    import wideqServer
    wideqServer.main()
else:
    print("You need Python 3.6 or newer. Sorry")
    sys.exit(1)
