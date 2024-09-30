#!/bin/bash

VERSION=0.1.0
SUBJECT=wget
USAGE="USAGE: $SUBJECT --url URL --output OUTNAME --working_dir OUTDIR"

# --- Option processing --------------------------------------------


while [[ $# -gt 1 ]]
do
key="$1"
case $key in
    --url)
    Url="$2"
    shift
    ;;
    --output)
    Outname="$2"
    shift
    ;;
    --working_dir)
    Tmpdir="$2"
    shift
    ;;
    --archiver)
    Archiver="$2"
    shift
    ;;
    --compressor)
    Compressor="$2"
    shift
    ;;
    *)
    # unknown option
    ;;
esac
shift # past argument or value
done

# Set defaults
Archiver=${Archiver:-'0'}     # options 'TAR'
Compressor=${Compressor:-'0'} # options 'GZIP', 'ZIP', 'BZIP2'
Tmpdir=${Tmpdir:-$(pwd)}

# Print parameters
echo '- Url='$Url
echo '- Archiver='$Archiver
echo '- Compressor='$Compressor

# Do things
cd $Tmpdir
if [ $Archiver = 'TAR' ]
then
    mkdir $Outname
fi

if [ $Archiver = '0' ]  && [ $Compressor = '0' ]; then
    echo "wget $Url -O $Outname"
    wget $Url -O $Outname

elif [ $Archiver = 'TAR' ] && [  $Compressor = 'GZIP' ]; then
    echo "wget $Url -O- | tar -xzv -C $Outname/"
    wget $Url -O- | tar -xzv -C $Outname/

elif [ $Archiver = 'TAR' ] && [ $Compressor = 'BZIP2' ]; then
    wget $Url -O- | tar -xjv -C $Outname/

elif [ $Compressor = 'GZIP' ]; then
    echo "wget $Url -O- | gunzip -c >  $Outname"
    wget $Url -O- | gunzip -c >  $Outname

elif [ $Compressor = 'BZIP2' ]; then
    wget $Url -O- | bunzip2 -c >  $Outname

elif [ $Compressor = 'ZIP' ]; then
    wget $Url -O output.zip
    unzip output.zip -d $Outname
    
else
   echo "ERROR: Not downloading file. The requested archiver '$Archiver' and/or compressor '$Compressor' are not supported."
   echo "Archiver: '0' for not unbundling the donwloaded folder; 'TAR' for applying 'untar' on the downloaded folder"
   echo "Compressor: '0' for not uncompressing the downloaded data; 'GZIP', 'ZIP', 'BZIP2' "
fi
