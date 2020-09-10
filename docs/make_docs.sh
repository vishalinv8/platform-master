#!/bin/bash

#
# This script converts Markdown .md files into HTML or PDF format.
#
# The files rest_json_api.md and rest_json_api_reference.md are the master
# source files. Those are the only files that should be edited by humans.
# The .html and .pdf versions are generated from the .md files using this script.
#

#
# Ubuntu 14.04 setup for creating documentation:
#
# sudo apt-get install pandoc
# sudo apt-get install pdflatex
# sudo apt-get install texlive-latex-base
# sudo apt-get install texlive-fonts-recommended
# sudo apt-get install texlive-latex-extra --no-install-recommends
#


# Concat the two source files into a single, large temporary file:
rm -f __temp__.md
cat rest_json_api.md >> __temp__.md
cat rest_json_api_reference.md >> __temp__.md

# pandoc args:
#   --toc: Generate a Table Of Contents
#   -o:    Output filename (extension determines format)

# PDF:
pandoc --toc -o rest_json_api.pdf __temp__.md

# HTML:
pandoc --toc --include-in-header pandoc.css -o rest_json_api.html __temp__.md

# Cleanup the temporary file:
rm __temp__.md
