<?php

declare(strict_types=1);

namespace Digital\PCode\Reader;

class ScriptTrackWave {

// F0 70 40 00 00 00 00 FF 47 BB 00 00 00 FF 13 23 40 01 00 00 00 FF TrackProperty[0:came_Num].Wave.Frequency = 1;
// F0 70 40 00 00 00 00 FF 47 BB 00 00 00 FF 13 24 43 FF TrackProperty[0:came_Num].Wave.Loop = True;
// F0 70 40 00 00 00 00 FF 47 BB 00 00 00 FF 13 25 40 01 00 00 00 FF TrackProperty[0:came_Num].Wave.Pan = 1;

// F2 01 00 00 80 46 F1 70 40 00 00 00 00 FF 47 BB 00 00 00 FF 13 27 FF b = TrackProperty[0:came_Num].Wave.Playing;

// F0 70 40 00 00 00 00 FF 47 BB 00 00 00 FF 13 10 40 01 00 00 00 FF TrackProperty[0:came_Num].Wave.Tag = 1;
// F0 70 40 00 00 00 00 FF 47 BB 00 00 00 FF 13 26 40 01 00 00 00 FF TrackProperty[0:came_Num].Wave.Volume = 1;
// F0 70 40 00 00 00 00 FF 47 BB 00 00 00 FF 13 30 43 FF TrackProperty[0:came_Num].Wave.FadeVol.Enabled = True;
// F0 70 40 00 00 00 00 FF 47 BB 00 00 00 FF 13 31 40 01 00 00 00 FF TrackProperty[0:came_Num].Wave.FadeVol.EndValue = 1; 
// F0 70 40 00 00 00 00 FF 47 BB 00 00 00 FF 13 32 40 01 00 00 00 FF TrackProperty[0:came_Num].Wave.FadeVol.Speed = 1;
// F0 70 40 00 00 00 00 FF 47 BB 00 00 00 FF 13 33 43 FF TrackProperty[0:came_Num].Wave.FadeVol.FadeEnd = True;
// F0 70 40 00 00 00 00 FF 47 BB 00 00 00 FF 13 40 43 FF TrackProperty[0:came_Num].Wave.FadeFreq.Enabled = True;
// F0 70 40 00 00 00 00 FF 47 BB 00 00 00 FF 13 41 40 01 00 00 00 FF TrackProperty[0:came_Num].Wave.FadeFreq.EndValue = 1; 
// F0 70 40 00 00 00 00 FF 47 BB 00 00 00 FF 13 42 40 01 00 00 00 FF TrackProperty[0:came_Num].Wave.FadeFreq.Speed = 1; 
// F0 70 40 00 00 00 00 FF 47 BB 00 00 00 FF 13 43 43 TrackProperty[0:came_Num].Wave.FadeFreq.FadeEnd = True;

}