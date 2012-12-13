LogBlockWorldPruner
===================

This script substantially reduces the size of large Minecraft worlds with the help of LogBlock. Mine dropped from 11gb to 2.7gb and everything is 100% the same as it was before.

The way it works is it prunes Minecraft regions that have no LogBlock history.  It doesn't delete any files and only copies them to a new directory.  You can then test the new region folder and eventually replace your existing folder.  I do suggest keeping the original as a backup just in case something goes wrong.

You need to set the parameters to connect to your LogBlock database and you need to set the source and destination directories.
