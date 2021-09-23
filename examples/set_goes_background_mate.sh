#!/bin/bash
#
# This file is part of the goes-php project
#
# Copyright (c) Tyler Holcomb <tyler@tholcomb.com>
#
# For the full copyright and license information, please view the LICENSE
# file that was distributed with this source code.
#

############################################################################################
# This script demonstrates how to set the desktop background in the MATE desktop environment
# The two calls to gsettings are necessary to reuse the filename
############################################################################################

IMAGE_PATH=/path/to/your/home/goes_background.jpg

php download_goes.php $IMAGE_PATH \
&& gsettings set org.mate.background picture-filename "" \
&& gsettings set org.mate.background picture-filename $IMAGE_PATH