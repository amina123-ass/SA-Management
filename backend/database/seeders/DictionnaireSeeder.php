<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DictionnaireSeeder extends Seeder
{
    public function run(): void
    {
        /* =========================
         * ÉTAT DOSSIER
         * ========================= */
        $etatDossiers = [
            ['code' => 'COMPLET', 'libelle' => 'Dossier complet', 'is_active' => true],
            ['code' => 'INCOMPLET', 'libelle' => 'Dossier incomplet', 'is_active' => true],
        ];
        
        foreach ($etatDossiers as $etat) {
            DB::table('etat_dossiers')->updateOrInsert(
                ['code' => $etat['code']],
                $etat
            );
        }

        /* =========================
         * NATURE DE DON
         * ========================= */
        $natureDons = [
            ['libelle' => 'Définitivement', 'is_active' => true],
            ['libelle' => 'À titre de prêt', 'is_active' => true],
        ];
        
        foreach ($natureDons as $nature) {
            DB::table('nature_dons')->updateOrInsert(
                ['libelle' => $nature['libelle']],
                $nature
            );
        }

        /* =========================
         * TYPE ASSISTANCE
         * ========================= */
        $typeAssistances = [
            ['id' => 1, 'libelle' => 'Lunettes', 'is_active' => true],
            ['id' => 2, 'libelle' => 'Appareil auditif', 'is_active' => true],
            ['id' => 3, 'libelle' => 'Transport médical', 'is_active' => true],
            ['id' => 4, 'libelle' => 'Matériel orthopédique', 'is_active' => true],
        ];
        
        foreach ($typeAssistances as $type) {
            DB::table('type_assistances')->updateOrInsert(
                ['id' => $type['id']],
                $type
            );
        }

        /* =========================
         * DÉTAILS TYPE ASSISTANCE
         * (lié à matériel orthopédique)
         * ========================= */
        $detailTypeAssistances = [
            ['type_assistance_id' => 4, 'libelle' => 'Béquille', 'is_active' => true],
            ['type_assistance_id' => 4, 'libelle' => 'Canne anglaise', 'is_active' => true],
            ['type_assistance_id' => 4, 'libelle' => 'Chaise roulante', 'is_active' => true],
        ];
        
        foreach ($detailTypeAssistances as $detail) {
            DB::table('detail_type_assistances')->updateOrInsert(
                ['type_assistance_id' => $detail['type_assistance_id'], 'libelle' => $detail['libelle']],
                $detail
            );
        }

        /* =========================
         * ÉTAT DE DON
         * ========================= */
        $etatDons = [
            ['code' => 'NEUF', 'libelle' => 'Neuf', 'is_active' => true],
            ['code' => 'ANCIEN', 'libelle' => 'Ancien', 'is_active' => true],
        ];
        
        foreach ($etatDons as $etat) {
            DB::table('etat_dons')->updateOrInsert(
                ['code' => $etat['code']],
                $etat
            );
        }

        /* =========================
         * COMMUNES
         * ========================= */
        $communes = [
            ['code' => 'TAFAJIGHT', 'nom' => 'Tafajight', 'is_active' => true],
            ['code' => 'DAR_ALHAMBRA', 'nom' => 'Dar Alhambra', 'is_active' => true],
            ['code' => 'ANDREJ', 'nom' => 'Andrej', 'is_active' => true],
            ['code' => 'IGHEZRANE', 'nom' => 'Ighezrane', 'is_active' => true],
            ['code' => 'EL_MENZEL', 'nom' => 'El Menzel', 'is_active' => true],
        ];
        
        foreach ($communes as $commune) {
            DB::table('communes')->updateOrInsert(
                ['code' => $commune['code']],
                $commune
            );
        }

        /* =========================
         * MILIEUX
         * ========================= */
        $milieux = [
            ['libelle' => 'Urbain', 'is_active' => true],
            ['libelle' => 'Rural', 'is_active' => true],
        ];
        
        foreach ($milieux as $milieu) {
            DB::table('milieux')->updateOrInsert(
                ['libelle' => $milieu['libelle']],
                $milieu
            );
        }

        
    }
}