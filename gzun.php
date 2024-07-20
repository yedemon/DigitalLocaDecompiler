<?php
// 假设你有一个名为 'example.gz' 的压缩文件
$compressedFile = '../2.zlib';
 
// 读取压缩文件的内容
$compressedData = file_get_contents($compressedFile);
 
// 解压缩数据
$uncompressedData = gzuncompress($compressedData);
 
// 如果你想将解压缩的数据写入一个新文件
$outputFile = '../2.unzlib';
file_put_contents($outputFile, $uncompressedData);