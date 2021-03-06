<?php
/* Osmium
 * Copyright (C) 2012 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Osmium\Page\EditSkillset;

require __DIR__.'/../inc/root.php';

if(!\Osmium\State\is_logged_in()) {
	\Osmium\fatal(403, "You are not logged in.");
}

$a = \Osmium\State\get_state('a');
$name = $_GET['name'];

$row = \Osmium\Db\fetch_assoc(
	\Osmium\Db\query_params(
		'SELECT importedskillset, overriddenskillset FROM osmium.accountcharacters WHERE accountid = $1 AND name = $2',
		array($a['accountid'], $name)
		));

if($row === false) {
	\Osmium\fatal(404, "No such character.");
}

$levels = array(
	null => 'Untrained',
	0 => 'Level 0',
	1 => 'Level I',
	2 => 'Level II',
	3 => 'Level III',
	4 => 'Level IV',
	5 => 'Level V',
	);
$imported = $row['importedskillset'] !== null ? json_decode($row['importedskillset'], true) : array();
$overridden = $row['overriddenskillset'] !== null ? json_decode($row['overriddenskillset'], true) : array();

$t = 'Edit skills of '.htmlspecialchars($name);
\Osmium\Chrome\print_header($t, '..');
echo "<h1>$t</h1>\n";

echo "<form method='post' action='".htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES)."'>\n<table id='e_skillset' class='d'>\n<tbody>\n";

$q = \Osmium\Db\query('SELECT typeid, typename, groupname FROM osmium.invskills ORDER BY groupname ASC, typename ASC');
$lastgroup = null;
while($s = \Osmium\Db\fetch_assoc($q)) {
	echo "<tr>\n";

	if(isset($_POST['override'][$s['typeid']])) {
		$v = $_POST['override'][$s['typeid']];
		if($v == -2) {
			unset($overridden[$s['typeid']]);
		} else {
			$v = max(0, min(5, intval($v)));
			$overridden[$s['typeid']] = $v;
		}
	}

	if($lastgroup !== $s['groupname']) {
		$lastgroup = $s['groupname'];
		echo "<th class='groupname' colspan='3'>".htmlspecialchars($s['groupname'])."</th>\n</tr>\n";
		echo "<tr>\n<th>Skill</th>\n<th>Imported level</th>\n<th>Overridden level</th>\n</tr>\n";
		echo "<tr>\n";
	}

	echo "<td>".htmlspecialchars($s['typename'])."</td>\n";

	$ilevel = isset($imported[$s['typeid']]) ? min(5, max(0, $imported[$s['typeid']])) : null;
	$olevel = isset($overridden[$s['typeid']]) ? min(5, max(0, $overridden[$s['typeid']])) : null;

	echo "<td>";
	if($olevel !== null) echo "<del>".$levels[$ilevel]."</del>";
	else echo $levels[$ilevel];
	echo "</td>\n";

	echo "<td><select name='override[".$s['typeid']."]'>\n";
	echo "<option value='-2'>No override</option>\n";
	foreach($levels as $k => $v) {
		if($k == null) continue;
		if($k === $olevel) {
			$selected = " selected='selected'";
		} else $selected = '';

		echo "<option value='$k'$selected>$v</option>\n";
	}
	echo "</select>\n";
	echo "<input type='submit' value='OK' />";
	echo "</td>\n";

	echo "</tr>\n";
}

echo "</tbody>\n</table>\n</form>\n";

\Osmium\Chrome\print_footer();

if(isset($_POST['override'])) {
	\Osmium\Db\query_params('UPDATE osmium.accountcharacters SET overriddenskillset = $1 WHERE accountid = $2 AND name = $3',
	                        array(
		                        json_encode($overridden),
		                        $a['accountid'],
		                        $name,
		                        ));
}