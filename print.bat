#/usr/bin/htmldoc --webpage $2$1.html --outfile $2$1.pdf
#"c:\Foxit Reader\Foxit Reader.exe" /p "%2%1.pdf" && exit
lp "$2$1.pdf"
