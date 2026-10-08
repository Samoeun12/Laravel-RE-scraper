<?php

namespace App\Services;

use App\Models\Property;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GeocodingService
{
    /**
     * Approximate bounding boxes for Cambodia's 25 provinces: [min_lat, max_lat, min_lng, max_lng]
     */
    public const PROVINCE_BOUNDS = [
        'Phnom Penh'       => [11.40, 11.75, 104.70, 105.10],
        'Siem Reap'        => [12.80, 14.10, 103.50, 104.60],
        'Preah Sihanouk'   => [10.30, 11.10, 103.20, 104.10],
        'Sihanoukville'    => [10.30, 11.10, 103.20, 104.10],
        'Kampot'           => [10.30, 11.10, 104.00, 104.80],
        'Battambang'       => [12.50, 13.60, 102.50, 103.70],
        'Kandal'           => [11.00, 12.00, 104.60, 105.35],
        'Kep'              => [10.40, 10.65, 104.25, 104.45],
        'Kampong Cham'     => [11.70, 12.50, 104.80, 105.80],
        'Koh Kong'         => [10.90, 12.10, 102.80, 103.80],
        'Kampong Speu'     => [11.10, 11.90, 104.00, 104.80],
        'Takeo'            => [10.75, 11.35, 104.50, 105.15],
        'Kampong Chhnang'  => [11.80, 12.60, 104.20, 105.00],
        'Kampong Thom'     => [12.30, 13.30, 104.40, 105.40],
        'Prey Veng'        => [11.10, 11.90, 105.10, 105.70],
        'Svay Rieng'       => [10.85, 11.45, 105.60, 106.25],
        'Pursat'           => [11.90, 12.80, 103.40, 104.30],
        'Banteay Meanchey' => [13.30, 14.10, 102.60, 103.50],
        'Pailin'           => [12.70, 13.00, 102.40, 102.75],
        'Kratie'           => [12.20, 13.30, 105.70, 106.50],
        'Stung Treng'      => [13.10, 14.30, 105.60, 106.60],
        'Ratanakiri'       => [13.20, 14.50, 106.60, 107.50],
        'Mondulkiri'       => [12.10, 13.20, 106.60, 107.60],
        'Mondul Kiri'      => [12.10, 13.20, 106.60, 107.60],
        'Preah Vihear'     => [13.40, 14.50, 104.40, 105.50],
        'Oddar Meanchey'   => [13.90, 14.50, 103.20, 104.20],
        'Tboung Khmum'     => [11.60, 12.30, 105.40, 106.10],
        'Tbuong Kmoum'     => [11.60, 12.30, 105.40, 106.10],
        'Bago (East)'      => [16.50, 19.50, 95.00, 97.50],
        'Mon'              => [14.80, 17.50, 96.80, 98.50],
        'Yangon'           => [16.30, 17.50, 95.70, 96.60],
        'Belait'           => [4.20, 4.80, 114.00, 114.60],
    ];

    /**
     * Accurate Khan / District Centroids & Maximum Plausible Radii (in km) in Phnom Penh.
     */
    public const KHAN_CENTROIDS = [
        'toul kork'        => [11.5732, 104.8988, 3.8],
        'tuol kouk'        => [11.5732, 104.8988, 3.8],
        'sen sok'          => [11.5830, 104.8624, 5.5],
        'saensokh'         => [11.5830, 104.8624, 5.5],
        'chbar ampov'      => [11.5292, 104.9567, 6.0],
        'chba ampov'       => [11.5292, 104.9567, 6.0],
        'chroy changvar'   => [11.6050, 104.9392, 6.5],
        'chrouy changva'   => [11.6050, 104.9392, 6.5],
        'chroy changva'    => [11.6050, 104.9392, 6.5],
        'por sen chey'     => [11.5450, 104.8214, 7.0],
        'por senchey'      => [11.5450, 104.8214, 7.0],
        'prek pnov'        => [11.6508, 104.8389, 7.0],
        'preaek pnov'      => [11.6508, 104.8389, 7.0],
        'kamboul'          => [11.5250, 104.7500, 7.0],
        'kambol'           => [11.5250, 104.7500, 7.0],
        'dangkao'          => [11.4789, 104.8722, 7.0],
        'mean chey'        => [11.5204, 104.9085, 5.0],
        'meanchey'         => [11.5204, 104.9085, 5.0],
        'russey keo'       => [11.6111, 104.9083, 5.0],
        'ruessei kaev'     => [11.6111, 104.9083, 5.0],
        'russei keo'       => [11.6111, 104.9083, 5.0],
        'daun penh'        => [11.5725, 104.9250, 3.2],
        'doun penh'        => [11.5725, 104.9250, 3.2],
        'chamkarmon'       => [11.5380, 104.9220, 3.2],
        'chamkar mon'      => [11.5380, 104.9220, 3.2],
        'boeng keng kang'  => [11.5505, 104.9265, 2.8],
        'boeung keng kang' => [11.5505, 104.9265, 2.8],
        '7 makara'         => [11.5630, 104.9150, 2.8],
        'prampir meakkakra'=> [11.5630, 104.9150, 2.8],
    ];

    /**
     * Canonical Target Centroids & Safe Jitter Radius for Precise Placement.
     */
    public const CENTROIDS = [
        // Phnom Penh Neighborhoods & Sangkats
        'bkk1'             => [11.5520, 104.9280, 0.005],
        'bkk2'             => [11.5490, 104.9210, 0.005],
        'bkk3'             => [11.5430, 104.9200, 0.005],
        'boeng keng kang'  => [11.5505, 104.9265, 0.008],
        'toul kork'        => [11.5732, 104.8988, 0.012],
        'daun penh'        => [11.5725, 104.9250, 0.010],
        'chamkarmon'       => [11.5380, 104.9220, 0.010],
        'toul tompoung'    => [11.5340, 104.9150, 0.006],
        '7 makara'         => [11.5630, 104.9150, 0.008],
        'sen sok'          => [11.5830, 104.8624, 0.018],
        'phnom penh thmey' => [11.5780, 104.8680, 0.012],
        'teuk thla'        => [11.5620, 104.8750, 0.010],
        'kouk khleang'     => [11.5950, 104.8580, 0.012],
        'khmuonh'          => [11.6050, 104.8450, 0.015],
        'chbar ampov'      => [11.5292, 104.9567, 0.018],
        'nirouth'          => [11.5350, 104.9600, 0.012],
        'preaek pra'       => [11.5150, 104.9650, 0.015],
        'preaek aeng'      => [11.5050, 104.9900, 0.018],
        'chroy changvar'   => [11.6050, 104.9392, 0.020],
        'mean chey'        => [11.5204, 104.9085, 0.015],
        'steung mean chey' => [11.5280, 104.8950, 0.012],
        'russey keo'       => [11.6111, 104.9083, 0.015],
        'tuol sangkae'     => [11.5950, 104.8950, 0.010],
        'por sen chey'     => [11.5450, 104.8214, 0.020],
        'chaom chau'       => [11.5250, 104.8100, 0.015],
        'kakab'            => [11.5550, 104.8350, 0.012],
        'dangkao'          => [11.4789, 104.8722, 0.020],
        'prek pnov'        => [11.6508, 104.8389, 0.020],
        'kamboul'          => [11.5250, 104.7500, 0.020],
        'phnom penh'       => [11.5564, 104.9282, 0.025],

        // Preah Sihanouk / Sihanoukville
        'krong preah sihanouk' => [10.6275, 103.5221, 0.018],
        'prey nob'             => [10.6500, 103.7500, 0.025],
        'koh rong'             => [10.7200, 103.2500, 0.020],
        'stueng hav'           => [10.7500, 103.5800, 0.020],
        'preah sihanouk'       => [10.6275, 103.5221, 0.025],
        'sihanoukville'        => [10.6275, 103.5221, 0.025],

        // Siem Reap
        'krong siem reap'  => [13.3671, 103.8448, 0.020],
        'banteay srei'     => [13.6000, 103.9500, 0.025],
        'prasat bakong'    => [13.3400, 103.9700, 0.025],
        'puok'             => [13.4300, 103.7200, 0.025],
        'siem reap'        => [13.3671, 103.8448, 0.025],

        // Kandal
        'krong ta khmau'   => [11.4800, 104.9500, 0.015],
        'ta khmau'         => [11.4800, 104.9500, 0.015],
        'kien svay'        => [11.4800, 105.0500, 0.020],
        'angk snuol'       => [11.5000, 104.7000, 0.025],
        'kandal stueng'     => [11.3800, 104.8800, 0.020],
        'khsach kandal'    => [11.6800, 105.0800, 0.025],
        'lvea aem'         => [11.5600, 105.0400, 0.020],
        'muk kampul'       => [11.7200, 104.9800, 0.020],
        'ponhea lueu'      => [11.7500, 104.8200, 0.020],
        'areiy ksatr'      => [11.5700, 104.9600, 0.015],
        'kandal'           => [11.4550, 104.9810, 0.035],

        // Kampot
        'krong kampot'     => [10.6104, 104.1815, 0.015],
        'tuek chhou'       => [10.6500, 104.1500, 0.020],
        'chhuk'            => [10.9200, 104.4500, 0.025],
        'kampong trach'    => [10.5500, 104.4500, 0.020],
        'kampot'           => [10.6104, 104.1815, 0.025],

        // Kampong Speu
        'krong chbar mon'  => [11.4533, 104.5209, 0.018],
        'phnum sruoch'     => [11.3800, 104.3000, 0.025],
        'kong pisei'       => [11.3000, 104.6500, 0.020],
        'samraong tong'    => [11.4800, 104.6000, 0.020],
        'kampong speu'     => [11.4533, 104.5209, 0.025],

        // Takeo
        'krong doun kaev'  => [10.9908, 104.7849, 0.018],
        'bati'             => [11.2500, 104.8000, 0.020],
        'tram kak'         => [11.0500, 104.6500, 0.020],
        'takeo'            => [10.9908, 104.7849, 0.025],

        // Battambang
        'krong battambang' => [13.0957, 103.2022, 0.018],
        'battambang'       => [13.0957, 103.2022, 0.025],

        // Other Provinces
        'kep'              => [10.4829, 104.3167, 0.018],
        'koh kong'         => [11.6153, 102.9838, 0.025],
        'kampong cham'     => [11.9924, 105.4645, 0.025],
        'kampong chhnang'  => [12.2500, 104.6667, 0.025],
        'sameakki mean chey'=> [12.0500, 104.6500, 0.025],
        'kampong thom'     => [12.7111, 104.8887, 0.025],
        'prey veng'        => [11.4868, 105.3253, 0.025],
        'svay rieng'       => [11.0879, 105.7994, 0.025],
        'krong bavet'      => [11.0800, 106.1500, 0.020],
        'pursat'           => [12.5388, 103.9192, 0.025],
        'banteay meanchey' => [13.5859, 102.9737, 0.025],
        'poipet'           => [13.6550, 102.5600, 0.018],
        'pailin'           => [12.8489, 102.6093, 0.020],
        'kratie'           => [12.4881, 106.0188, 0.025],
        'stung treng'      => [13.5259, 105.9683, 0.025],
        'ratanakiri'       => [13.7394, 106.9873, 0.025],
        'mondulkiri'       => [12.4558, 107.1881, 0.025],
        'mondul kiri'      => [12.4558, 107.1881, 0.025],
        'preah vihear'     => [13.8073, 104.9805, 0.025],
        'oddar meanchey'   => [14.1818, 103.5176, 0.025],
        'tboung khmum'     => [11.8891, 105.6593, 0.025],
        'tbuong kmoum'     => [11.8891, 105.6593, 0.025],

        // International Regions
        'bago (east)'      => [17.3352, 96.4817, 0.030],
        'bago'             => [17.3352, 96.4817, 0.030],
        'mon'              => [15.9622, 97.7323, 0.030],
        'thanbyuzayat'     => [15.9622, 97.7323, 0.020],
        'yangon'           => [16.8661, 96.1951, 0.030],
        'dagon myothit (south)' => [16.8150, 96.2200, 0.020],
        'belait'           => [4.5833, 114.2333, 0.020],
        'kuala belait'     => [4.5833, 114.2333, 0.020],
    ];

    /**
     * Canonical District Spellings.
     */
    public const DIST_CANONICAL = [
        'tuol kouk'        => 'Toul Kork',
        'toul kork'        => 'Toul Kork',
        'saensokh'         => 'Sen Sok',
        'sen sok'          => 'Sen Sok',
        'chbar ampov'      => 'Chbar Ampov',
        'chba ampov'       => 'Chbar Ampov',
        'chroy changvar'   => 'Chroy Changvar',
        'chrouy changva'   => 'Chroy Changvar',
        'chroy changva'    => 'Chroy Changvar',
        'chamkar mon'      => 'Chamkarmon',
        'chamkarmon'       => 'Chamkarmon',
        'doun penh'        => 'Daun Penh',
        'daun penh'        => 'Daun Penh',
        'boeng keng kang'  => 'Boeng Keng Kang',
        'boeung keng kang' => 'Boeng Keng Kang',
        'por senchey'      => 'Por Sen Chey',
        'por sen chey'     => 'Por Sen Chey',
        'meanchey'         => 'Mean Chey',
        'mean chey'        => 'Mean Chey',
        'ruessei kaev'     => 'Russey Keo',
        'russei keo'       => 'Russey Keo',
        'russey keo'       => 'Russey Keo',
        'dangkao'          => 'Dangkao',
        'preaek pnov'      => 'Prek Pnov',
        'prek pnov'        => 'Prek Pnov',
        'kambol'           => 'Kamboul',
        'kamboul'          => 'Kamboul',
        '7 makara'         => '7 Makara',
        'prampir meakkakra'=> '7 Makara',
        'krong ta khmau'   => 'Krong Ta Khmau',
        'ta khmau'         => 'Krong Ta Khmau',
        'takhmao'          => 'Krong Ta Khmau',
        'krong preah sihanouk' => 'Krong Preah Sihanouk',
        'krong siem reap'  => 'Krong Siem Reap',
        'krong doun kaev'  => 'Krong Doun Kaev',
        'krong kampot'     => 'Krong Kampot',
        'krong battambang' => 'Krong Battambang',
    ];

    /**
     * Khmer Text to Province/District Mapper with exclusions.
     */
    public const KHMER_LOOKUP = [
        ['ព្រះសីហនុ', 'Preah Sihanouk', 'Krong Preah Sihanouk'],
        ['កំពង់សោម', 'Preah Sihanouk', 'Krong Preah Sihanouk'],
        ['សៀមរាប', 'Siem Reap', 'Krong Siem Reap'],
        ['កណ្តាល', 'Kandal', null],
        ['កំពត', 'Kampot', 'Krong Kampot'],
        ['កំពង់ស្ពឺ', 'Kampong Speu', 'Krong Chbar Mon'],
        ['តាកែវ', 'Takeo', 'Krong Doun Kaev'],
        ['បាត់ដំបង', 'Battambang', 'Krong Battambang'],
        ['កែប', 'Kep', 'Krong Kep'],
        ['កោះកុង', 'Koh Kong', 'Krong Khemara Phoumin'],
        ['កំពង់ចាម', 'Kampong Cham', 'Krong Kampong Cham'],
        ['កំពង់ឆ្នាំង', 'Kampong Chhnang', 'Krong Kampong Chhnang'],
        ['កំពង់ធំ', 'Kampong Thom', 'Krong Steung Saen'],
        ['ព្រៃវែង', 'Prey Veng', 'Krong Prey Veng'],
        ['ស្វាយរៀង', 'Svay Rieng', 'Krong Svay Rieng'],
        ['ពោធិ៍សាត់', 'Pursat', 'Krong Pursat'],
        ['បន្ទាយមានជ័យ', 'Banteay Meanchey', 'Krong Serei Saophoan'],
        ['ប៉ៃលិន', 'Pailin', 'Krong Pailin'],
        ['ក្រចេះ', 'Kratie', 'Krong Kratie'],
        ['ស្ទឹងត្រែង', 'Stung Treng', 'Krong Stung Treng'],
        ['រតនគិរី', 'Ratanakiri', 'Krong Banlung'],
        ['មណ្ឌលគិរី', 'Mondulkiri', 'Krong Saen Monourom'],
        ['ព្រះវិហារ', 'Preah Vihear', 'Krong Tbaeng Meanchey'],
        ['ឧត្តរមានជ័យ', 'Oddar Meanchey', 'Krong Samraong'],
        ['ត្បូងឃ្មុំ', 'Tboung Khmum', 'Krong Suong'],
        ['ទួលគោក', 'Phnom Penh', 'Toul Kork'],
        ['សែនសុខ', 'Phnom Penh', 'Sen Sok'],
        ['ច្បារអំពៅ', 'Phnom Penh', 'Chbar Ampov'],
        ['ជ្រោយចង្វារ', 'Phnom Penh', 'Chroy Changvar'],
        ['ចំការមន', 'Phnom Penh', 'Chamkarmon'],
        ['ដូនពេញ', 'Phnom Penh', 'Daun Penh'],
        ['បឹងកេងកង', 'Phnom Penh', 'Boeng Keng Kang'],
        ['ពោធិ៍សែនជ័យ', 'Phnom Penh', 'Por Sen Chey'],
        ['មានជ័យ', 'Phnom Penh', 'Mean Chey'],
        ['ឬស្សីកែវ', 'Phnom Penh', 'Russey Keo'],
        ['ដង្កោ', 'Phnom Penh', 'Dangkao'],
        ['ព្រែកព្នៅ', 'Phnom Penh', 'Prek Pnov'],
        ['កំបូល', 'Phnom Penh', 'Kamboul'],
        ['៧មករា', 'Phnom Penh', '7 Makara'],
        ['៧ មករា', 'Phnom Penh', '7 Makara'],
        ['តាខ្មៅ', 'Kandal', 'Krong Ta Khmau'],
        ['អរិយក្សត្រ', 'Kandal', 'Areiy Ksatr'],
        ['កៀនស្វាយ', 'Kandal', 'Kien Svay'],
        ['អង្គស្នួល', 'Kandal', 'Angk Snuol'],
        ['បាទី', 'Takeo', 'Bati'],
        ['ព្រៃនប់', 'Preah Sihanouk', 'Prey Nob'],
        ['កោះរ៉ុង', 'Preah Sihanouk', 'Koh Rong'],
        ['ដូនកែវ', 'Takeo', 'Krong Doun Kaev'],
        ['សែនមនោរម្យ', 'Mondulkiri', 'Krong Saen Monourom'],
        ['បាវិត', 'Svay Rieng', 'Krong Bavet'],
    ];

    /**
     * Calculate Great Circle distance between two lat/lng pairs in kilometers.
     */
    public static function distanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    /**
     * Resolve and validate coordinates for a property.
     * Preserves accurate original coordinates, while correcting misplaced pins.
     *
     * @return array [float $lat, float $lng, string $province, ?string $district, bool $wasAdjusted]
     */
    public static function resolveCoordinates(
        ?float $lat,
        ?float $lng,
        ?string $province,
        ?string $district,
        ?string $commune = null,
        ?string $title = null,
        ?string $location = null,
        int $seedId = 0
    ): array {
        $origProv = trim((string) $province);
        $origDist = trim((string) $district);
        $textHaystack = mb_strtolower("{$origProv} {$origDist} {$commune} {$location} {$title}");

        $resolvedProv = $origProv ?: 'Phnom Penh';
        $resolvedDist = $origDist;

        // 1. Khmer keyword matching (avoiding 'ឈូក' false match for 'ឈូកវ៉ា')
        foreach (self::KHMER_LOOKUP as [$kh, $pMatch, $dMatch]) {
            if (str_contains($textHaystack, $kh)) {
                if (empty($origProv) || $origProv === 'Phnom Penh' || $pMatch !== 'Phnom Penh') {
                    $resolvedProv = $pMatch;
                }
                if ($dMatch && (empty($resolvedDist) || in_array($resolvedDist, ['Krong', 'District']))) {
                    $resolvedDist = $dMatch;
                }
                break;
            }
        }

        // 2. Canonicalize district
        $distLower = mb_strtolower($resolvedDist);
        if (isset(self::DIST_CANONICAL[$distLower])) {
            $resolvedDist = self::DIST_CANONICAL[$distLower];
        }

        // 3. Known districts that strictly imply their province
        if (in_array($resolvedDist, ['Krong Preah Sihanouk', 'Prey Nob', 'Koh Rong', 'Stueng Hav'])) {
            $resolvedProv = 'Preah Sihanouk';
        } elseif (in_array($resolvedDist, ['Krong Siem Reap', 'Banteay Srei', 'Prasat Bakong', 'Puok'])) {
            $resolvedProv = 'Siem Reap';
        } elseif (in_array($resolvedDist, ['Krong Ta Khmau', 'Kien Svay', 'Angk Snuol', 'Kandal Stueng', 'Khsach Kandal', 'Lvea Aem', 'Muk Kampul', 'Ponhea Lueu', 'Areiy Ksatr'])) {
            $resolvedProv = 'Kandal';
        } elseif (in_array($resolvedDist, ['Chhuk', 'Tuek Chhou', 'Krong Kampot', 'Kampot', 'Kampong Trach']) && !str_contains($textHaystack, 'ឈូកវ៉ា')) {
            $resolvedProv = 'Kampot';
        } elseif (in_array($resolvedDist, ['Phnum Sruoch', 'Kong Pisei', 'Samraong Tong', 'Krong Chbar Mon'])) {
            $resolvedProv = 'Kampong Speu';
        } elseif (in_array($resolvedDist, ['Krong Doun Kaev', 'Bati', 'Tram Kak'])) {
            $resolvedProv = 'Takeo';
        } elseif (in_array($resolvedDist, ['Krong Bavet', 'Krong Svay Rieng'])) {
            $resolvedProv = 'Svay Rieng';
        } elseif (in_array($resolvedDist, ['Krong Saen Monourom'])) {
            $resolvedProv = 'Mondulkiri';
        } elseif (in_array($resolvedDist, ['Sameakki Mean Chey'])) {
            $resolvedProv = 'Kampong Chhnang';
        } elseif (isset(self::KHAN_CENTROIDS[$distLower])) {
            $resolvedProv = 'Phnom Penh';
        }

        // 4. Validate existing coordinates
        $isValid = true;

        if ($lat === null || $lng === null || $lat < 0.0 || $lat > 30.0 || $lng < 90.0 || $lng > 125.0 || ($lat == 0.0 && $lng == 0.0)) {
            $isValid = false;
        } elseif ($resolvedProv !== 'Phnom Penh' && $resolvedProv !== 'Kandal' && ($lat >= 11.45 && $lat <= 11.65 && $lng >= 104.80 && $lng <= 105.00)) {
            // Provincial property mistakenly dropped in central Phnom Penh
            $isValid = false;
        } elseif (isset(self::PROVINCE_BOUNDS[$resolvedProv])) {
            [$pMinLat, $pMaxLat, $pMinLng, $pMaxLng] = self::PROVINCE_BOUNDS[$resolvedProv];
            if ($lat < $pMinLat || $lat > $pMaxLat || $lng < $pMinLng || $lng > $pMaxLng) {
                $isValid = false;
            }
        }

        // Check Khan distance within Phnom Penh
        if ($isValid && $resolvedProv === 'Phnom Penh' && isset(self::KHAN_CENTROIDS[$distLower])) {
            [$cLat, $cLng, $maxKm] = self::KHAN_CENTROIDS[$distLower];
            if (self::distanceKm($lat, $lng, $cLat, $cLng) > $maxKm) {
                $isValid = false;
            }
        }

        // 5. If accurate, return as is
        if ($isValid) {
            return [
                'lat' => round($lat, 6),
                'lng' => round($lng, 6),
                'province' => $resolvedProv,
                'district' => $resolvedDist ?: null,
                'was_adjusted' => false,
            ];
        }

        // 6. Coordinates are invalid/misplaced - determine accurate centroid
        $targetLat = null;
        $targetLng = null;
        $targetRad = 0.015;

        $searchKeys = [
            mb_strtolower((string) $resolvedDist),
            mb_strtolower((string) $commune),
            mb_strtolower((string) $resolvedProv),
        ];

        foreach ($searchKeys as $k) {
            if (!empty($k) && isset(self::CENTROIDS[$k])) {
                [$targetLat, $targetLng, $targetRad] = self::CENTROIDS[$k];
                break;
            }
        }

        if (!$targetLat) {
            foreach (self::CENTROIDS as $cKey => $cVal) {
                if (str_contains($textHaystack, $cKey)) {
                    [$targetLat, $targetLng, $targetRad] = $cVal;
                    break;
                }
            }
        }

        if (!$targetLat) {
            $pKey = mb_strtolower($resolvedProv);
            if (isset(self::CENTROIDS[$pKey])) {
                [$targetLat, $targetLng, $targetRad] = self::CENTROIDS[$pKey];
            } else {
                [$targetLat, $targetLng, $targetRad] = [11.5564, 104.9282, 0.020];
            }
        }

        // Deterministic pseudo-random jitter seeded by listing ID or hash
        $seed = $seedId > 0 ? $seedId : crc32($title . $location);
        mt_srand($seed);
        $jitterLat = ((mt_rand() / mt_getrandmax()) * 2 - 1) * $targetRad;
        $jitterLng = ((mt_rand() / mt_getrandmax()) * 2 - 1) * $targetRad;

        return [
            'lat' => round($targetLat + $jitterLat, 6),
            'lng' => round($targetLng + $jitterLng, 6),
            'province' => $resolvedProv,
            'district' => $resolvedDist ?: null,
            'was_adjusted' => true,
        ];
    }

    /**
     * Repair all misplaced listings in the properties table.
     */
    public static function repairAllProperties(bool $dryRun = false): array
    {
        $properties = DB::table('properties')->get([
            'id', 'source', 'title', 'province', 'district', 'commune', 'location', 'latitude', 'longitude'
        ]);

        $updatedCount = 0;
        $totalCount = $properties->count();

        foreach ($properties as $prop) {
            $res = self::resolveCoordinates(
                $prop->latitude ? (float) $prop->latitude : null,
                $prop->longitude ? (float) $prop->longitude : null,
                $prop->province,
                $prop->district,
                $prop->commune,
                $prop->title,
                $prop->location,
                $prop->id
            );

            if ($res['was_adjusted'] || $res['province'] !== $prop->province || $res['district'] !== $prop->district) {
                if (!$dryRun) {
                    DB::table('properties')->where('id', $prop->id)->update([
                        'latitude'   => $res['lat'],
                        'longitude'  => $res['lng'],
                        'province'   => $res['province'],
                        'district'   => $res['district'],
                        'updated_at' => now(),
                    ]);
                }
                $updatedCount++;
            }
        }

        return [
            'total'   => $totalCount,
            'updated' => $updatedCount,
        ];
    }
}
