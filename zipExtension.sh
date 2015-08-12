#!/bin/bash

mkdir -p tmp-install/app/etc/modules
mkdir -p tmp-install/app/code/community/Reachly

cp package.xml tmp-install
cp Reachly_All.xml tmp-install/app/etc/modules
cp -r HandleEvent tmp-install/app/code/community/Reachly

cd tmp-install
zip -r ../Reachly.zip *
cd ..
rm -rf tmp-install
