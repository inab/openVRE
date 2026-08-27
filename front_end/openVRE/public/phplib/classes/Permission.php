<?php

namespace OpenVRE;


enum Permission: string
{
    case DeleteFile = "DeleteFile";
    case DownloadFile = "DownloadFile";
    case EditFile = "EditFile";
    case MoveFile = "MoveFile";
    case ReadFile = "ReadFile";
}
