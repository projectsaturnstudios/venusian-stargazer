<?php

namespace ProjectSaturnStudios\Stargazer\DONKI\Enums;

enum DonkiNotificationType: string
{
    case ALL = 'all';
    case FLR = 'FLR';
    case SEP = 'SEP';
    case CME = 'CME';
    case IPS = 'IPS';
    case MPC = 'MPC';
    case GST = 'GST';
    case RBE = 'RBE';
    case REPORT = 'report';
}
