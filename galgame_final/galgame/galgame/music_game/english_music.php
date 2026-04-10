<?php
declare(strict_types=1);

// Scan the current folder for local MP3 files
function getLocalSongs(): array
{
    $audioFiles = glob(__DIR__ . DIRECTORY_SEPARATOR . '*.mp3') ?: [];
    sort($audioFiles, SORT_NATURAL | SORT_FLAG_CASE);

    $songs = [];

    // Build the song list from local file names
    foreach (array_values($audioFiles) as $index => $filePath) {
        $baseName = pathinfo($filePath, PATHINFO_FILENAME);
        $artist = 'Unknown Artist';
        $title = $baseName;

        // If the file name follows "Artist - Title", split it into two parts
        if (str_contains($baseName, ' - ')) {
            [$artist, $title] = explode(' - ', $baseName, 2);
        }

        $songs[] = [
            'title' => $title,
            'artist' => $artist,
            'cover' => 'singer.jpg',
            'audio' => 'stream.php?index=' . $index,
            'description' => 'Local MP3 file loaded from your current music folder.'
        ];
    }

    return $songs;
}

// Load all local songs
$songs = getLocalSongs();

// Fallback data if there are no MP3 files in the folder
if ($songs === []) {
    $songs[] = [
        'title' => 'No MP3 Found',
        'artist' => 'Local Folder',
        'cover' => 'singer.jpg',
        'audio' => '',
        'description' => 'Put MP3 files in this folder and refresh the page.'
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <!-- Make the page responsive on mobile devices -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Page title shown in the browser tab -->
    <title>Campus English Music Festival</title>

    <!-- Main stylesheet for the music festival page -->
    <link rel="stylesheet" href="english_music.css">
</head>
<body>
    <!-- Back button to return to the main game page -->
    <button class="back-to-game-btn" onclick="window.location.href='../index.html'">
        ← Back to game
    </button>

    <!-- Decorative background glow effects -->
    <div class="poster-glow poster-glow-left"></div>
    <div class="poster-glow poster-glow-right"></div>

    <!-- Decorative festival lights -->
    <div class="festival-lights">
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
    </div>

    <div class="festival-shell">
        <!-- Top scrolling headline strip -->
        <section class="marquee-strip" aria-label="Festival headline">
            <div class="marquee-track">
                <span>Campus Night Fest</span>
                <span>Open Air English Songs</span>
                <span>Student Union Stage</span>
                <span><?php echo count($songs); ?> Local Tracks</span>
                <span>Live Lawn Atmosphere</span>
                <span>Campus Night Fest</span>
                <span>Open Air English Songs</span>
                <span>Student Union Stage</span>
            </div>
        </section>

        <!-- Main hero section -->
        <section class="festival-hero">
            <div class="hero-copy">
                <span class="hero-badge">Campus Music Festival</span>
                <p class="hero-date">Friday Night 19:30 / North Lawn Stage</p>
                <h1>English Audio Festival</h1>
                <p class="hero-text">
                    A campus-style listening page powered by your own local MP3 collection.
                    No video, no jump-out link, just direct audio playback inside the page.
                </p>

                <!-- Small visual tags under the hero text -->
                <div class="hero-tags">
                    <span>Live House Mood</span>
                    <span>Student Union Stage</span>
                    <span>Open Air Night</span>
                </div>

                <!-- Summary stats for the page -->
                <div class="hero-stats">
                    <article class="hero-stat">
                        <strong><?php echo count($songs); ?></strong>
                        <span>songs tonight</span>
                    </article>
                    <article class="hero-stat">
                        <strong>audio</strong>
                        <span>local streaming</span>
                    </article>
                    <article class="hero-stat">
                        <strong>campus</strong>
                        <span>festival poster UI</span>
                    </article>
                </div>
            </div>

            <!-- Cover image / poster section -->
            <div class="hero-visual">
                <div class="hero-frame">
                    <img id="coverImage" src="<?php echo htmlspecialchars($songs[0]['cover'], ENT_QUOTES, 'UTF-8'); ?>" alt="Campus festival singer poster">
                    <div class="frame-sticker sticker-top">Guest Singer</div>
                    <div class="frame-sticker sticker-bottom">Audio Only</div>
                </div>
            </div>
        </section>

        <!-- Main content area: playlist on the left, player on the right -->
        <section class="festival-grid">
            <!-- Playlist / lineup section -->
            <aside class="lineup-card">
                <div class="card-heading">
                    <span>Tonight Lineup</span>
                    <strong><?php echo count($songs); ?> Tracks</strong>
                </div>

                <!-- Render all songs as clickable playlist items -->
                <ul class="playlist" id="playlist">
                    <?php foreach ($songs as $index => $song): ?>
                        <li>
                            <button
                                class="playlist-item<?php echo $index === 0 ? ' active' : ''; ?>"
                                data-index="<?php echo $index; ?>"
                                type="button"
                            >
                                <span class="playlist-order"><?php echo str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT); ?></span>
                                <span class="playlist-meta">
                                    <strong><?php echo htmlspecialchars($song['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <small><?php echo htmlspecialchars($song['artist'], ENT_QUOTES, 'UTF-8'); ?></small>
                                </span>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </aside>

            <!-- Main player / song detail section -->
            <main class="stage-card">
                <div class="stage-head">
                    <div>
                        <span class="eyebrow">Now On Stage</span>
                        <h2 id="songTitle"><?php echo htmlspecialchars($songs[0]['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                        <p class="artist" id="songArtist"><?php echo htmlspecialchars($songs[0]['artist'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div class="stage-pill">Main Stage</div>
                </div>

                <!-- Current song description -->
                <p class="description" id="songDescription"><?php echo htmlspecialchars($songs[0]['description'], ENT_QUOTES, 'UTF-8'); ?></p>

                <!-- Dashboard area with mood, visualizer, and playback desk info -->
                <div class="stage-dashboard">
                    <div class="dashboard-card">
                        <span class="dashboard-label">Stage Mood</span>
                        <strong id="moodLabel">Golden Hour Set</strong>
                        <p>Warm lights, campus lawn, and a custom-built music desk for your playlist.</p>
                    </div>
                    <div class="dashboard-card visualizer-card">
                        <span class="dashboard-label">Live Meter</span>
                        <div class="visualizer" aria-hidden="true">
                            <span></span>
                            <span></span>
                            <span></span>
                            <span></span>
                            <span></span>
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                    <div class="dashboard-card">
                        <span class="dashboard-label">Playback Desk</span>
                        <strong id="deskLabel">Ready for soundcheck</strong>
                        <p>Use the custom controls below to run the set like a small campus stage operator.</p>
                    </div>
                </div>

                <!-- Audio player area -->
                <div class="player-strip">
                    <div class="progress-card">
                        <!-- Hidden native audio element used for playback -->
                        <audio
                            id="audioPlayer"
                            preload="metadata"
                            class="audio-player"
                            src="<?php echo htmlspecialchars($songs[0]['audio'], ENT_QUOTES, 'UTF-8'); ?>"
                        >
                            Your browser does not support the audio element.
                        </audio>

                        <!-- Playback status row -->
                        <div class="player-status-row">
                            <span class="status-chip" id="playState">Ready</span>
                            <span class="status-note">Custom Player</span>
                        </div>

                        <!-- Seek bar -->
                        <input id="progressBar" type="range" min="0" max="0" step="0.1" value="0">

                        <!-- Current time / total duration -->
                        <div class="time-row">
                            <span id="currentTime">00:00</span>
                            <span id="duration">00:00</span>
                        </div>
                    </div>

                    <!-- Audio control buttons -->
                    <div class="controls">
                        <button id="prevBtn" class="icon-btn" type="button" aria-label="Previous track">
                            <span class="icon icon-prev" aria-hidden="true">
                                <span></span>
                                <span></span>
                            </span>
                        </button>

                        <button id="playBtn" class="primary icon-btn play-btn" type="button" aria-label="Play">
                            <span class="icon icon-play" aria-hidden="true">
                                <span></span>
                            </span>
                        </button>

                        <button id="nextBtn" class="icon-btn" type="button" aria-label="Next track">
                            <span class="icon icon-next" aria-hidden="true">
                                <span></span>
                                <span></span>
                            </span>
                        </button>
                    </div>

                    <!-- Small helper note below the controls -->
                    <p class="helper-note">
                        This version now streams local MP3 files through a seekable PHP audio endpoint.
                    </p>
                </div>

                <!-- Extra information cards -->
                <div class="info-panels">
                    <article class="info-card">
                        <span class="mini-label">Festival Notes</span>
                        <p>Built for a school showcase, club event page, or an English-song listening interface.</p>
                    </article>
                    <article class="info-card">
                        <span class="mini-label">Visual Theme</span>
                        <p>Grass tones, poster paper texture, sunset orange lights, and a lively student-stage mood.</p>
                    </article>
                    <article class="info-card wide-card">
                        <span class="mini-label">Campus Route</span>
                        <p>Ticket booth, club recruitment corner, rehearsal lawn, and finally the night stage. The whole page now reads more like an event poster than a plain player.</p>
                    </article>
                </div>
            </main>
        </section>
    </div>

    <!-- Export PHP song data to JavaScript -->
    <script>
        window.songList = <?php echo json_encode($songs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    </script>

    <!-- Main player logic -->
    <script src="english_music.js"></script>
</body>
</html>