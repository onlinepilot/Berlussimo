<?php

class geldkonto_info {
    /* Diese Vars werden von geld_konto_details($konto_id) gesetzt */
    var $konto_beguenstigster;
    var $kontonummer;
    var $blz;
    var $kredit_institut;

    /* Tabelle mit allen Geldkonten */
    function alle_geldkonten_tabelle_kontostand() {
        $result = mysql_query ( "SELECT GELD_KONTEN.KONTO_ID, GELD_KONTEN.BEZEICHNUNG, GELD_KONTEN.BEGUENSTIGTER, GELD_KONTEN.KONTONUMMER, GELD_KONTEN.BLZ, GELD_KONTEN.INSTITUT, IBAN, BIC FROM GELD_KONTEN WHERE  GELD_KONTEN.AKTUELL = '1' ORDER BY GELD_KONTEN.BEGUENSTIGTER ASC" );

        $numrows = mysql_numrows ( $result );
        if ($numrows > 0) {
            $zaehler = 0;
            while ( $row = mysql_fetch_assoc ( $result ) )
                $my_array [] = $row;

            echo "<table class=\"sortable striped\">";
            // echo "<tr class=\"feldernamen\"><td>KONTO ID</td><td>BEZEICHNUNG</td><td>KONTONUMMER</td><td align=right>KONTOSTAND</td></tr>";
            echo "<tr><th>Konto</th><th>Bezeichnung</th><th>Begünstigter</th><th width=\"200\">IBAN</th><th>BIC</th><th>Kontostand</th><th>Option</th><th>Option</th></tr>";
            for($a = 0; $a < count ( $my_array ); $a ++) {
                $zaehler ++;
                $konto_id = $my_array [$a] ['KONTO_ID'];
                $konto_bezeichnung = $my_array [$a] ['BEZEICHNUNG'];
                $beguenstigter = $my_array [$a] ['BEGUENSTIGTER'];
                $kontonummer = $my_array [$a] ['KONTONUMMER'];
                $blz = $my_array [$a] ['BLZ'];
                $geld_institut = $my_array [$a] ['INSTITUT'];
                /*
                 * $sep = new sepa();
                 * $sep->get_iban_bic($kontonummer, $blz);
                 * $iban = $sep->IBAN1;
                 * $bic = $sep->BIC;
                 */
                $iban = chunk_split ( $my_array [$a] ['IBAN'], 4, ' ' );
                // $iban_1 = chunk_split($iban, 4, ' ');
                $bic = $my_array [$a] ['BIC'];
                $konto_stand_aktuell = nummer_punkt2komma_t ( $this->geld_konto_stand ( $konto_id ) );
                // $konto_stand_aktuell ='';
                $detail_link = "<a class=\"table_links\" href='" . route('legacy::details::index', ['option' => 'details_anzeigen', 'detail_tabelle' => 'GELD_KONTEN', 'detail_id' => $konto_id]) . "'>Details</a>";
                $link_aendern = "<a class=\"table_links\" href='" . route('legacy::geldkonten::index', ['option' => 'gk_aendern', 'gk_id' => $konto_id]) . "'>GK ändern</a>";
                if ($zaehler == 1) {
                    echo "<tr class=\"zeile1\"><td>$konto_id</td><td>$konto_bezeichnung</td><td>$beguenstigter</td><td>$iban</td><td>$bic</td><td align=right>$konto_stand_aktuell €</td><td>$detail_link</td><td>$link_aendern</td></tr>";
                }
                if ($zaehler == 2) {
                    echo "<tr class=\"zeile2\"><td>$konto_id</td><td>$konto_bezeichnung</td><td>$beguenstigter</td><td>$iban</td><td>$bic</td><td align=right>$konto_stand_aktuell €</td><td>$detail_link</td><td>$link_aendern</td></tr>";
                    $zaehler = 0;
                }
            }
            echo "</table>";
        } else {
            echo "<b>Keine Geldkonten vorhanden</b>";
            return FALSE;
        }
    }
    function kosten_monatlich($monat, $jahr, $geldkonto_id) {
        $letzter_tag = date ( "t", mktime ( 0, 0, 0, $monat, 1, $jahr ) );
        // echo $letzter_tag;
        $anfangsdatum = $jahr . '-' . $monat . '-1';
        $end_datum = $jahr . '-' . $monat . '-' . $letzter_tag;
        $result = mysql_query ( "SELECT SUM(GELD_KONTO_BUCHUNGEN.BETRAG) AS GESAMTKOSTEN_MONATLICH FROM GELD_KONTO_BUCHUNGEN WHERE DATUM BETWEEN '$anfangsdatum' AND '$end_datum' && GELD_KONTO_BUCHUNGEN.GELDKONTO_ID='$geldkonto_id' && AKTUELL='1' && KONTENRAHMEN_KONTO!='80001'" );
        $row = mysql_fetch_assoc ( $result );
        return $row ['GESAMTKOSTEN_MONATLICH'] . "</br>";
        // echo "$anfangsdatum bis $end_datum<br>";
    }
    function mieten_monatlich($monat, $jahr, $geldkonto_id) {
        $letzter_tag = date ( "t", mktime ( 0, 0, 0, $monat, 1, $jahr ) );
        // echo $letzter_tag;
        $anfangsdatum = $jahr . '-' . $monat . '-1';
        $end_datum = $jahr . '-' . $monat . '-' . $letzter_tag;
        $result = mysql_query ( "SELECT SUM(BETRAG) AS MIETEINNAHMEN_MONATLICH FROM GELD_KONTO_BUCHUNGEN WHERE DATUM BETWEEN '$anfangsdatum' AND '$end_datum' && GELDKONTO_ID='$geldkonto_id' && KONTENRAHMEN_KONTO='80001' && AKTUELL='1'" );

        $row = mysql_fetch_assoc ( $result );
        return $row ['MIETEINNAHMEN_MONATLICH'] . "</br>";
        // echo "$anfangsdatum bis $end_datum<br>";
    }
    function summe_kosten_objekt_zeitraum($geldkonto_id, $von_m, $von_j, $bis_m, $bis_j) {
        $zeit = new zeitraum ();
        $zeitraum_arr = $zeit->zeitraum_generieren ( $von_m, $von_j, $bis_m, $bis_j );
        // print_r($zeitraum_arr);
        $kosten_gesamt = '0.00';
        for($b = 0; $b < count ( $zeitraum_arr ); $b ++) {
            $monat = $zeitraum_arr [$b] ['monat'];
            $jahr = $zeitraum_arr [$b] ['jahr'];
            $kosten_gesamt = $kosten_gesamt + $this->kosten_monatlich ( $monat, $jahr, $geldkonto_id );
        }
        return $kosten_gesamt;
    }
    function summe_mieten_objekt_zeitraum($geldkonto_id, $von_m, $von_j, $bis_m, $bis_j) {
        $zeit = new zeitraum ();
        $zeitraum_arr = $zeit->zeitraum_generieren ( $von_m, $von_j, $bis_m, $bis_j );
        // print_r($zeitraum_arr);
        $kosten_gesamt = '0.00';
        for($b = 0; $b < count ( $zeitraum_arr ); $b ++) {
            $monat = $zeitraum_arr [$b] ['monat'];
            $jahr = $zeitraum_arr [$b] ['jahr'];
            $kosten_gesamt = $kosten_gesamt + $this->mieten_monatlich ( $monat, $jahr, $geldkonto_id );
        }
        return $kosten_gesamt;
    }

    /* Tabelle mit allen Geldkonten */
    function alle_geldkonten_tabelle() {
        $result = mysql_query ( "SELECT GELD_KONTEN.KONTO_ID, GELD_KONTEN.BEZEICHNUNG, GELD_KONTEN.BEGUENSTIGTER, GELD_KONTEN.KONTONUMMER, GELD_KONTEN.BLZ, GELD_KONTEN.INSTITUT FROM GELD_KONTEN WHERE  GELD_KONTEN.AKTUELL = '1' ORDER BY GELD_KONTEN.KONTO_ID ASC" );

        $numrows = mysql_numrows ( $result );
        if ($numrows > 0) {
            while ( $row = mysql_fetch_assoc ( $result ) )
                $my_array [] = $row;

            echo "<table class=\"sortable\">";
            // echo "<tr class=\"feldernamen\"><td>KONTO ID</td><td>BEZEICHNUNG</td><td>KONTONUMMER</td><td>MIETEINNAHMEN</td><td>KOSTEN</td><td>KONTOSTAND</td></tr>";
            echo "<tr><th>KONTO</th><th>BEZEICHNUNG</th><th>KONTONUMMER</th><th>KONTOSTAND</th></tr>";
            for($a = 0; $a < count ( $my_array ); $a ++) {
                $konto_id = $my_array [$a] ['KONTO_ID'];
                $konto_bezeichnung = $my_array [$a] ['BEZEICHNUNG'];
                $beguenstigter = $my_array [$a] ['BEGUENSTIGTER'];
                $kontonummer = $my_array [$a] ['KONTONUMMER'];
                $blz = $my_array [$a] ['BLZ'];
                $geld_institut = $my_array [$a] ['INSTITUT'];
                $summe_mieteinnahmen = $this->summe_mieteinnahmen ( $konto_id );
                // $summe_andere_buchungen = $this->summe_geld_konto_buchungen($konto_id);
                // $konto_stand_aktuell = $summe_mieteinnahmen + $summe_andere_buchungen;
                $kostengesamt = $this->summe_kosten_objekt_zeitraum ( $konto_id, '1', '2006', '4', '2009' );
                $mietengesamt = $this->summe_mieten_objekt_zeitraum ( $konto_id, '1', '2006', '4', '2009' );
                $konto_stand_monatsende = $mietengesamt + $kostengesamt;
                echo "<tr><td>$konto_id</td><td>$konto_bezeichnung</td><td>$kontonummer</td><td>$mietengesamt</td><td>$kostengesamt</td><td>$konto_stand_monatsende</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<b>Keine Geldkonten vorhanden</b>";
            return FALSE;
        }
    }

    /* Funktion zur Erstellung eines Dropdowns für Empfangsgeldkonto */
    function dropdown_geldkonten($kostentraeger_typ, $kostentraeger_id) {
        $result = mysql_query ( "SELECT GELD_KONTEN.KONTO_ID, GELD_KONTEN.BEGUENSTIGTER, GELD_KONTEN.KONTONUMMER, GELD_KONTEN.BLZ, GELD_KONTEN.INSTITUT FROM GELD_KONTEN_ZUWEISUNG, GELD_KONTEN WHERE KOSTENTRAEGER_TYP = '$kostentraeger_typ' && KOSTENTRAEGER_ID = '$kostentraeger_id' && GELD_KONTEN.KONTO_ID = GELD_KONTEN_ZUWEISUNG.KONTO_ID && GELD_KONTEN_ZUWEISUNG.AKTUELL = '1' && GELD_KONTEN.AKTUELL = '1' ORDER BY GELD_KONTEN.KONTO_ID ASC" );

        $numrows = mysql_numrows ( $result );
        if ($numrows > 0) {
            while ( $row = mysql_fetch_assoc ( $result ) )
                $my_array [] = $row;

            echo "<label for=\"geld_konto_dropdown\">&nbsp;Bankverbindung - $kostentraeger_typ &nbsp;</label><select name=\"geld_konto\" id=\"geld_konto_dropdown\" size=\"1\" >";
            for($a = 0; $a < count ( $my_array ); $a ++) {
                $konto_id = $my_array [$a] ['KONTO_ID'];
                $beguenstigter = $my_array [$a] ['BEGUENSTIGTER'];
                $kontonummer = $my_array [$a] ['KONTONUMMER'];
                $blz = $my_array [$a] ['BLZ'];
                $geld_institut = $my_array [$a] ['INSTITUT'];
                if (session()->has('geldkonto_id') && session()->get('geldkonto_id') == $konto_id) {
                    echo "<option value=\"$konto_id\" selected>$geld_institut - Knr:$kontonummer - Blz: $blz</option>\n";
                } else {
                    echo "<option value=\"$konto_id\">$geld_institut - Knr:$kontonummer - Blz: $blz</option>\n";
                }
            }
            echo "</select>";
        } else {
            echo "<b>Kein Geldkonto hinterlegt bzw zugewiesen</b>";
            return FALSE;
        }
    }

    /* Funktion zur Erstellung eines Dropdowns für Empfangsgeldkonto */
    function dropdown_geldkonten_alle($label, $kostentraeger_typ, $kostentraeger_id) {
        $result = mysql_query ( "SELECT GELD_KONTEN.KONTO_ID, GELD_KONTEN.BEGUENSTIGTER, GELD_KONTEN.KONTONUMMER, GELD_KONTEN.BLZ, GELD_KONTEN.INSTITUT FROM GELD_KONTEN_ZUWEISUNG, GELD_KONTEN WHERE KOSTENTRAEGER_TYP = '$kostentraeger_typ' && KOSTENTRAEGER_ID = '$kostentraeger_id' && GELD_KONTEN.KONTO_ID = GELD_KONTEN_ZUWEISUNG.KONTO_ID && GELD_KONTEN_ZUWEISUNG.AKTUELL = '1' && GELD_KONTEN.AKTUELL = '1' ORDER BY GELD_KONTEN.KONTO_ID ASC" );

        $numrows = mysql_numrows ( $result );
        if ($numrows > 0) {
            while ( $row = mysql_fetch_assoc ( $result ) )
                $my_array [] = $row;

            echo "<label for=\"geld_konto_dropdown\">$label</label>\n<select name=\"geld_konto\" id=\"geld_konto_dropdown\" size=\"1\" >\n";
            for($a = 0; $a < count ( $my_array ); $a ++) {
                $konto_id = $my_array [$a] ['KONTO_ID'];
                $beguenstigter = $my_array [$a] ['BEGUENSTIGTER'];
                $kontonummer = $my_array [$a] ['KONTONUMMER'];
                $blz = $my_array [$a] ['BLZ'];
                $geld_institut = $my_array [$a] ['INSTITUT'];
                if (session()->has('geldkonto_id') && session()->get('geldkonto_id') == $konto_id) {
                    echo "<option value=\"$konto_id\" selected>Knr:$kontonummer - Blz: $blz</option>\n";
                } else {
                    echo "<option value=\"$konto_id\" >Knr:$kontonummer - Blz: $blz</option>\n";
                }
            } // end for
            echo "</select>\n";
        } else {
            echo "<b>Kein Geldkonto hinterlegt bzw zugewiesen</b>";
            return FALSE;
        }
    }

    /* Funktion zur Erstellung eines Dropdowns für Empfangsgeldkonto */
    function dropdown_geldkonten_k($label, $name, $id, $kostentraeger_typ, $kostentraeger_id) {
        $result = mysql_query ( "SELECT GELD_KONTEN.KONTO_ID, GELD_KONTEN.BEGUENSTIGTER, GELD_KONTEN.KONTONUMMER, GELD_KONTEN.BLZ, GELD_KONTEN.INSTITUT FROM GELD_KONTEN_ZUWEISUNG, GELD_KONTEN WHERE KOSTENTRAEGER_TYP = '$kostentraeger_typ' && KOSTENTRAEGER_ID = '$kostentraeger_id' && GELD_KONTEN.KONTO_ID = GELD_KONTEN_ZUWEISUNG.KONTO_ID && GELD_KONTEN_ZUWEISUNG.AKTUELL = '1' && GELD_KONTEN.AKTUELL = '1' ORDER BY GELD_KONTEN.KONTO_ID ASC" );

        $numrows = mysql_numrows ( $result );
        if ($numrows > 0) {
            while ( $row = mysql_fetch_assoc ( $result ) )
                $my_array [] = $row;

            echo "<label for=\"$id\">$label</label>\n<select name=\"$name\" id=\"$id\" size=\"1\" >\n";
            for($a = 0; $a < count ( $my_array ); $a ++) {
                $konto_id = $my_array [$a] ['KONTO_ID'];
                $beguenstigter = $my_array [$a] ['BEGUENSTIGTER'];
                $kontonummer = $my_array [$a] ['KONTONUMMER'];
                $blz = $my_array [$a] ['BLZ'];
                $geld_institut = $my_array [$a] ['INSTITUT'];
                if (session()->has('geldkonto_id') && session()->get('geldkonto_id') == $konto_id) {
                    echo "<option value=\"$konto_id\" selected>Knr:$kontonummer - Blz: $blz</option>\n";
                } else {
                    echo "<option value=\"$konto_id\" >Knr:$kontonummer - Blz: $blz</option>\n";
                }
            } // end for
            echo "</select>\n";
        } else {
            echo "<b>Kein Geldkonto hinterlegt bzw zugewiesen</b>";
            return FALSE;
        }
    }
    function geld_konto_details($konto_id) {
        $result = mysql_query ( "SELECT BEGUENSTIGTER, KONTONUMMER, BLZ, INSTITUT, BEZEICHNUNG, BIC, IBAN  FROM GELD_KONTEN WHERE KONTO_ID='$konto_id' && AKTUELL='1' ORDER BY KONTO_DAT DESC LIMIT 0,1" );
        $row = mysql_fetch_assoc ( $result );
        /*
         * $this->konto_beguenstigter = $row['BEGUENSTIGTER'];
         * $this->kontonummer = $row['KONTONUMMER'];
         * $this->blz = $row['BLZ'];
         * $this->kredit_institut = $row['INSTITUT'];
         * $this->geldkonto_bezeichnung = $row['BEZEICHNUNG'];
         * $this->geldkonto_bezeichnung_kurz = $this->geldkonto_bezeichnung;
         */
        $this->IBAN = $row ['IBAN'];
        $this->IBAN1 = trim(chunk_split ( $this->IBAN, 4, ' ' ));
        $this->BIC = $row ['BIC'];

        $this->beguenstigter = $row ['BEGUENSTIGTER'];
        $this->konto_beguenstigter = $row ['BEGUENSTIGTER'];

        $this->kontonummer = $row ['KONTONUMMER'];
        $this->blz = $row ['BLZ'];

        $this->bankname = $row ['INSTITUT'];
        $this->institut = $row ['INSTITUT'];
        $this->kredit_institut = $row ['INSTITUT'];

        $this->geldkonto_bez = $row ['BEZEICHNUNG'];
        $this->geldkonto_bezeichnung = $row ['BEZEICHNUNG'];
        $this->geldkonto_bezeichnung_kurz = $this->geldkonto_bezeichnung;
    }

    /* Funktion zur Ermittlung der Anzahl der Geldkonten */
    function geldkonten_anzahl($kostentraeger_typ, $kostentraeger_id) {
        $result = mysql_query ( "SELECT GELD_KONTEN.KONTO_ID, GELD_KONTEN.BEGUENSTIGTER, GELD_KONTEN.KONTONUMMER, GELD_KONTEN.BLZ, GELD_KONTEN.INSTITUT FROM GELD_KONTEN_ZUWEISUNG, GELD_KONTEN WHERE KOSTENTRAEGER_TYP = '$kostentraeger_typ' && KOSTENTRAEGER_ID = '$kostentraeger_id' && GELD_KONTEN.KONTO_ID = GELD_KONTEN_ZUWEISUNG.KONTO_ID && GELD_KONTEN_ZUWEISUNG.AKTUELL = '1' && GELD_KONTEN.AKTUELL = '1' ORDER BY GELD_KONTEN.KONTO_ID ASC" );
        $numrows = mysql_numrows ( $result );
        return $numrows;
    }

    /* Funktion zur Ermittlung der Anzahl der Geldkonten */
    function geldkonten_arr($kostentraeger_typ, $kostentraeger_id) {
        $result = mysql_query ( "SELECT GELD_KONTEN.KONTO_ID, GELD_KONTEN.BEGUENSTIGTER, GELD_KONTEN.IBAN, GELD_KONTEN.BIC, GELD_KONTEN.KONTONUMMER, GELD_KONTEN.BLZ, GELD_KONTEN.INSTITUT  FROM GELD_KONTEN_ZUWEISUNG, GELD_KONTEN WHERE KOSTENTRAEGER_TYP = '$kostentraeger_typ' && KOSTENTRAEGER_ID = '$kostentraeger_id' && GELD_KONTEN.KONTO_ID = GELD_KONTEN_ZUWEISUNG.KONTO_ID && GELD_KONTEN_ZUWEISUNG.AKTUELL = '1' && GELD_KONTEN.AKTUELL = '1' ORDER BY GELD_KONTEN.KONTO_ID ASC" );
        $numrows = mysql_numrows ( $result );
        if ($numrows > 0) {
            while ( $row = mysql_fetch_assoc ( $result ) )
                $my_array [] = $row;
            return $my_array;
        } else {
            return FALSE;
        }
    }

    /* Diese Funktion ermittelt Geldkontonummern und zeigt sie im Dropdown */
    function geld_konten_ermitteln($kostentraeger_typ, $kostentraeger_id) {
        // echo "$kostentraeger_typ $kostentraeger_id<br>";
        $geldkonten_anzahl = $this->geldkonten_anzahl ( $kostentraeger_typ, $kostentraeger_id );
        if ($geldkonten_anzahl > 0) {
            $this->dropdown_geldkonten ( $kostentraeger_typ, $kostentraeger_id );
        } else {
            if ($kostentraeger_typ == 'Mietvertrag') {
                $mietvertrag_info = new mietvertrag ();
                $einheit_id = $mietvertrag_info->get_einheit_id_von_mietvertrag ( $kostentraeger_id );
                $this->geld_konten_ermitteln ( 'Einheit', $einheit_id );
                // echo "<h3>Mietvertrag $kostentraeger_id Einheit: $einheit_id </h3>";
            }

            if ($kostentraeger_typ == 'Einheit') {
                $einheit_info = new einheit ();
                $einheit_info->get_einheit_info ( $kostentraeger_id );
                $this->geld_konten_ermitteln ( 'Haus', $einheit_info->haus_id );
                // echo "<h3>Einheit $kostentraeger_id Haus: ".$einheit_info->haus_id." </h3>";
            }

            if ($kostentraeger_typ == 'Haus') {
                $haus_info = new haus ();
                $haus_info->get_haus_info ( $kostentraeger_id );
                $this->geld_konten_ermitteln ( 'Objekt', $haus_info->objekt_id );
                // echo "<h3>Haus $kostentraeger_id Objekt: ".$haus_info->objekt_id." fffff</h3>";
            }

            if ($kostentraeger_typ == 'Objekt') {
                // echo "BLAdfdfd $o->objekt_eigentuemer_id";
                $o = new objekt ();
                $o->get_objekt_infos ( $kostentraeger_id );
                // echo "BLA $o->objekt_eigentuemer_id";
                $this->geld_konten_ermitteln ( 'Partner', $o->objekt_eigentuemer_id );
                // $this->geld_konten_ermitteln('Objekt', $kostentraeger_id);
                // echo "<h3>Haus $kostentraeger_id Objekt: ".$haus_info->objekt_id." </h3>";
            }
        }
    }

    /* Diese Funktion ermittelt Geldkontonummern und seztzt die erste Kontonummer im object an */
    function geld_konto_ermitteln($kostentraeger_typ, $kostentraeger_id) {
        // echo "<h1>$kostentraeger_typ $kostentraeger_id<br>";
        $geldkonten_anzahl = $this->geldkonten_anzahl ( $kostentraeger_typ, $kostentraeger_id );
        if ($geldkonten_anzahl) {
            // $this->dropdown_geldkonten($kostentraeger_typ, $kostentraeger_id);
            $result = mysql_query ( "SELECT GELD_KONTEN.KONTO_ID, GELD_KONTEN.BEGUENSTIGTER, GELD_KONTEN.KONTONUMMER, GELD_KONTEN.BLZ, GELD_KONTEN.BEZEICHNUNG, GELD_KONTEN.INSTITUT, GELD_KONTEN.IBAN, GELD_KONTEN.BIC FROM GELD_KONTEN_ZUWEISUNG, GELD_KONTEN WHERE KOSTENTRAEGER_TYP = '$kostentraeger_typ' && KOSTENTRAEGER_ID = '$kostentraeger_id' && GELD_KONTEN.KONTO_ID = GELD_KONTEN_ZUWEISUNG.KONTO_ID && GELD_KONTEN_ZUWEISUNG.AKTUELL = '1' && GELD_KONTEN.AKTUELL = '1' ORDER BY GELD_KONTEN.KONTO_ID ASC LIMIT 0,1" );
            $numrows = mysql_numrows ( $result );
            if ($numrows) {
                $row = mysql_fetch_assoc ( $result );
                unset ( $this->geldkonto_id );
                $this->geldkonto_id = $row ['KONTO_ID'];
                $this->beguenstigter = umbruch_entfernen ( $row ['BEGUENSTIGTER'] );
                $this->kontonummer = $row ['KONTONUMMER'];
                $this->blz = $row ['BLZ'];
                $this->bez = $row ['BEZEICHNUNG'];
                $this->IBAN = $row ['IBAN'];
                $this->IBAN1 = chunk_split ( $this->IBAN, 4, ' ' );
                $this->BIC = $row ['BIC'];
                $this->geld_institut = $row ['INSTITUT'];
                /*
                 * $sep = new sepa();
                 * $sep->get_iban_bic($this->kontonummer, $this->blz);
                 * $this->BIC = $sep->BIC;
                 * $this->IBAN = $sep->IBAN;
                 * $this->IBAN1 = $sep->IBAN1; //4 stellig
                 */
            }
        } else {
            if ($kostentraeger_typ == 'Mietvertrag') {
                $mietvertrag_info = new mietvertrag ();
                $einheit_id = $mietvertrag_info->get_einheit_id_von_mietvertrag ( $kostentraeger_id );
                $this->geld_konten_ermitteln ( 'Einheit', $einheit_id );
                // echo "<h3>Mietvertrag $kostentraeger_id Einheit: $einheit_id </h3>";
            }

            if ($kostentraeger_typ == 'Einheit') {
                $einheit_info = new einheit ();
                $einheit_info->get_einheit_info ( $kostentraeger_id );
                $this->geld_konten_ermitteln ( 'Haus', $einheit_info->haus_id );
                // echo "<h3>Einheit $kostentraeger_id Haus: ".$einheit_info->haus_id." </h3>";
            }

            if ($kostentraeger_typ == 'Haus') {
                $haus_info = new haus ();
                $haus_info->get_haus_info ( $kostentraeger_id );
                $this->geld_konten_ermitteln ( 'Objekt', $haus_info->objekt_id );
                // echo "<h3>Haus $kostentraeger_id Objekt: ".$haus_info->objekt_id." </h3>";
            }

            if ($kostentraeger_typ == 'Objekt') {
                $o = new objekt ();
                $o->get_objekt_infos ( $kostentraeger_id );
                $this->geld_konten_ermitteln ( 'Partner', $o->objekt_eigentuemer_id );
                // echo "<h1>$kostentraeger_typ $kostentraeger_id";
            }
        }
    }

    /*
     * var $objekt_id;
     * var $objekt_name;
     * var $haus_id;
     * var $haus_strasse;
     * var $haus_nummer;
     * var $einheit_kurzname;
     * var $einheit_qm;
     * var $einheit_lage;
     * var $anzahl_einheiten;
     * var $haus_plz;
     * var $haus_stadt;
     * var $datum_heute;
     * var $mietvertrag_id;
     * function get_einheit_info($einheit_id){
     */

    /* Funktionen bezogen auf Geldbewegungen auf dem Geldkonto */
    function summe_geld_konto_buchungen($geld_konto_id) {
        $result = mysql_query ( "SELECT sum( BETRAG ) AS KONTOSTAND_GELDBUCHUNGEN
FROM `GELD_KONTO_BUCHUNGEN` WHERE GELDKONTO_ID = '$geld_konto_id' && AKTUELL = '1'" );
        $numrows = mysql_numrows ( $result );
        if ($numrows > 0) {
            $row = mysql_fetch_assoc ( $result );
            return $row ['KONTOSTAND_GELDBUCHUNGEN'];
        } else {
            return FALSE;
        }
    }
    function summe_geld_konto_buchungen_kontiert($geld_konto_id, $kontenrahmen_konto) {
        $result = mysql_query ( "SELECT sum( BETRAG ) AS KONTOSTAND_GELDBUCHUNGEN_KONTIERT
FROM `GELD_KONTO_BUCHUNGEN` WHERE GELDKONTO_ID = '$geld_konto_id' && AKTUELL = '1' && KONTENRAHMEN_KONTO = '$kontenrahmen_konto'" );
        $numrows = mysql_numrows ( $result );
        if ($numrows > 0) {
            $row = mysql_fetch_assoc ( $result );
            return $row ['KONTOSTAND_GELDBUCHUNGEN_KONTIERT'];
        } else {
            return FALSE;
        }
    }
    function geld_konto_buchungen_zeitraum($geld_konto_id, $datum_von, $datum_bis) {
        $result = mysql_query ( "SELECT * AS KONTOSTAND_GELDBUCHUNGEN
FROM `GELD_KONTO_BUCHUNGEN` WHERE GELDKONTO_ID = '$geld_konto_id' && AKTUELL = '1' && DATUM BETWEEN '$datum_von' AND '$datum_bis'" );
        $numrows = mysql_numrows ( $result );
        if ($numrows > 0) {
            while ( $row = mysql_fetch_assoc ( $result ) )
                $my_array [] = $row;
            return $my_array;
        } else {
            return FALSE;
        }
    }

    /* Funktionen bezogen auf Mieteinnahmen auf dem Geldkonto */
    function summe_mieteinnahmen($geld_konto_id) {
        $result = mysql_query ( "SELECT sum( BETRAG ) AS SUMME_MIETEINNAHMEN
FROM `GELD_KONTO_BUCHUNGEN` WHERE GELDKONTO_ID = '$geld_konto_id' && KONTENRAHMEN_KONTO='80001' &&  AKTUELL = '1'" );
        $numrows = mysql_numrows ( $result );
        if ($numrows > 0) {
            $row = mysql_fetch_assoc ( $result );
            return $row ['SUMME_MIETEINNAHMEN'];
        } else {
            return FALSE;
        }
    }
    function summe_mieteinnahmen_zeitraum($geld_konto_id, $datum_von, $datum_bis) {
        $result = mysql_query ( "SELECT sum( BETRAG ) AS SUMME_MIETEINNAHMEN
FROM `GELD_KONTO_BUCHUNGEN` WHERE GELDKONTO_ID = '$geld_konto_id' && KONTENRAHMEN_KONTO='80001' && AKTUELL = '1' && DATUM BETWEEN '$datum_von' AND '$datum_bis'" );
        $numrows = mysql_numrows ( $result );
        if ($numrows > 0) {
            $row = mysql_fetch_assoc ( $result );
            return $row ['SUMME_MIETEINNAHMEN'];
        } else {
            return FALSE;
        }
    }
    function geld_konto_stand_ausgeben($geld_konto_id) {
        $result = mysql_query ( "SELECT sum( BETRAG ) AS KONTOSTAND
FROM `GELD_KONTO_BUCHUNGEN` WHERE GELDKONTO_ID = '$geld_konto_id' && AKTUELL = '1'" );
        $numrows = mysql_numrows ( $result );
        if ($numrows > 0) {
            $row = mysql_fetch_assoc ( $result );
            echo "<table><tr><td><h1>Kontostand: $row[KONTOSTAND] €</h1></td></tr></table>";
        } else {
            return FALSE;
        }
    }
    function geld_konto_stand($geld_konto_id) {
        $result = mysql_query ( "SELECT sum( BETRAG ) AS KONTOSTAND
FROM `GELD_KONTO_BUCHUNGEN` WHERE GELDKONTO_ID = '$geld_konto_id' && AKTUELL = '1'" );
        $numrows = mysql_numrows ( $result );
        if ($numrows > 0) {
            $row = mysql_fetch_assoc ( $result );
            return $row ['KONTOSTAND'];
        } else {
            return FALSE;
        }
    }
    function einnahmen_uebersicht_geldkonten() {
        /*
         * SELECT GELDKONTO_ID, BEGUENSTIGTER, KONTONUMMER, BLZ, INSTITUT, SUM( BETRAG ) AS KONTOSTAND
         * FROM `GELD_KONTO_BUCHUNGEN` JOIN ( GELD_KONTEN) ON (GELD_KONTO_BUCHUNGEN.GELDKONTO_ID = GELD_KONTEN.KONTO_ID)
         * GROUP BY GELDKONTO_ID
         * LIMIT 0 , 30
         *
         * SELECT KONTO AS GELDKONTO_ID, BEGUENSTIGTER, KONTONUMMER, BLZ, INSTITUT, SUM( BETRAG ) AS KONTOSTAND
         * FROM `MIETE_ZAHLBETRAG`
         * JOIN (
         * GELD_KONTEN
         * ) ON ( MIETE_ZAHLBETRAG.KONTO = GELD_KONTEN.KONTO_ID )
         * GROUP BY GELDKONTO_ID
         * LIMIT 0 , 30
         */
    }
    function geld_konto_ein_ausgaben($geld_konto_id) {
        /*
         * SELECT a.summe_mieteinnahmen, b.summe_anderezahlbetrage, a.summe_mieteinnahmen + b.summe_anderezahlbetrage AS KONTOSTAND
         * FROM (
         *
         * SELECT sum( BETRAG ) AS summe_mieteinnahmen
         * FROM MIETE_ZAHLBETRAG
         * WHERE KONTO = '2'
         * )a, (
         *
         * SELECT sum( BETRAG ) AS summe_anderezahlbetrage
         * FROM GELD_KONTO_BUCHUNGEN
         * WHERE GELDKONTO_ID = '2'
         * )b
         */
    }
} // ende class geldkonto