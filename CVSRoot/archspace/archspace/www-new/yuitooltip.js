var PlanetAttrib = new Array();
PlanetAttrib[0] = new Array('Ancient Ruins','+2 Research','+2 Commerce', '+1 Genius CM (global, not cumulative)');
PlanetAttrib[1] = new Array('Artifact','+10 Research');
PlanetAttrib[2] = new Array('Asteroid','+2 Production');
PlanetAttrib[3] = new Array('Beautiful Landscape','+3 Commerce','-1 Spy');
PlanetAttrib[4] = new Array('Black Hole','-2 Environment','-3 Commerce', '-2 Production');
PlanetAttrib[5] = new Array('Cognition Amplifier Relic','+3 Favility Cost','+6 production', '+1 Production CM (global, not cumulative)');
PlanetAttrib[6] = new Array('Dark Nebula','-2 Environment','-2 Commerce');
PlanetAttrib[7] = new Array('Frontier Area','-2 Commerce');
PlanetAttrib[8] = new Array('Gravity Controlled','+2 Environment');
PlanetAttrib[9] = new Array('Hostile Monster','-1 Environment','Fixed by Primitive Language');
PlanetAttrib[10] = new Array('Intense Volcanic Activity','-2 Environment','+10 Production');
PlanetAttrib[11] = new Array('Irregular Climate','-2 Environment');
PlanetAttrib[12] = new Array('Lost Trabotulin Library','+30 Research','+5 Military','+10 Commerce','+1 Facility Cost CM (global, not cumulative)','+5 Research CM (global, not cumulative)','+1 Military CM (global, not cumulative)');
PlanetAttrib[13] = new Array('Maintenance Center','+3 Facility Cost','+2 Commerce','+2 Growth');
PlanetAttrib[14] = new Array('Major Space Route','+5 Commerce');
PlanetAttrib[15] = new Array('Massive Artifact','+20 Research');
PlanetAttrib[16] = new Array('Military Stronghold','+15 Military','-5 Commerce','+1 Military CM (global, not cumulative)');
PlanetAttrib[17] = new Array('Moon','+3 Military','+3 Growth','+2 Commerce');
PlanetAttrib[18] = new Array('Moon Cluster','+4 Military','+3 Growth','+5 Commerce','+1 Facility Cost');
PlanetAttrib[19] = new Array('Nebula','+2 Military','+2 Research','+2 Commerce');
PlanetAttrib[20] = new Array('Obstinate Microbe','-2 Environment','Fixed by Genetic Therapy');
PlanetAttrib[21] = new Array('Oceanic','+1 Environment','+3 Growth','-4 Facility Cost','+4 Research');
PlanetAttrib[22] = new Array('Radiation','-1 Environment','Fixed by Solar Manipulation');
PlanetAttrib[23] = new Array('Rare Ore','+3 Production','+2 Commerce');
PlanetAttrib[24] = new Array('Severe Radiation','-2 Environment','Fixed by Solar Manipulation');
PlanetAttrib[25] = new Array('Ship Yard','+5 Military');
PlanetAttrib[26] = new Array('Volcanic Activity','-1 Environment','+4 Production');
PlanetAttrib[27] = new Array('Underground Caverns','+1 Facility Cost','+1 Military','+3 Growth');
PlanetAttrib[28] = new Array('Major Space Crossroutes','+10 Commerce','+1 Commerce CM (global, not cumulative)');

function paGenerateIDfromName(name)
{
for(var i = 0; i < PlanetAttrib.length; i++)
{
    if(PlanetAttrib[i][0] == name)
	return i;
}
//failed to find the name
alert('failed to find ' + name);
return 0;
}
function paGenerateTooltipText(id)
{
var tempString = new String();
for(var i = 0; i < PlanetAttrib[id].length; i++)
{
    if(i<1)
    {
	tempString += '<span class="planetAttribTitle">'+PlanetAttrib[id][i] + '<span/><br/>';
    }
    else
    {
	tempString += '<span class="planetAttribElement">'+PlanetAttrib[id][i] + '<span/><br/>';
    }

}
return tempString;
}
var Admiral = new Array();
Admiral[0] = new Array('Artifact Cooling Engine', 'Races: Xesperados', 'Cooling Time reduction up to 45%','Detection/Interpretation penalty up to -5');
Admiral[1] = new Array('Artifact Crystal', 'Races: Xerusian', 'Beam Damage bonus up to 40%','Shield Recharge Rate bonus up to 40%','Speed bonus up to 40%');
Admiral[2] = new Array('Ballistic Expert', 'Races: All', 'Projectile Cooling Time reduction up to 40%','Projectile Attack Rate bonus up to 40%','Projectile Damage bonus up to 40%');
Admiral[3] = new Array('Blitzkrieg', 'Races: Xerusian', 'Damage bonus up to 20%','Critical Hit bonus up to 5%','Maneuver bonus up to +7');
Admiral[4] = new Array('Breeder Male', 'Races: Targoid', 'Bonus to all abilities +2','Fleet Commanding bonus up to +8','Efficiency bonus up to +10%','Repair Speed bonus up to 40%','Disappears randomly to perform "duties"');
Admiral[5] = new Array('Clonal Double', 'Races: Targoid', 'Survival chance up to 90%','Defense Rate bonus up to 40%','Efficiency penalty (up to lvl 13)');
Admiral[6] = new Array('Commerce King', 'Races: Buckaneer', 'HP bonus up to 50%','Commerce CM bonus when on Expedition mission, up to +3');
Admiral[7] = new Array('Consciousness Crystal', 'Races: Xeloss', 'Beam Damage bonus up to 25%','PSI Attack bonus up to 40%','Attack Rate bonus up to 15%');
Admiral[8] = new Array('Cyber Scan Unit', 'Races: Evintos, Tecanoid', 'Attack Rate bonus up to 10%','Weak Cloaking Detection','Detection/Interpretation bonus up to +7');
Admiral[9] = new Array('Defensive Matrix', 'Races: Xesperados', 'Damage reduction up to 30%','Panic Modifier -10');
Admiral[10] = new Array('DNA Poison Replicator', 'Races: Targoid', 'Missile Damage bonus up to 30%','Projectile Damage bonus up to 40%');
Admiral[11] = new Array('Energy System Specialist', 'Races: All', 'Beam Cooling Time reduction up to 40%','Beam Attack Rate bonus up to 40%','Beam Damage bonus up to 40%');
Admiral[12] = new Array('Engineering Specialist', 'Races: All', 'Armor Defense Rate bonus up to 40%','Repair bonus (lvl 6+) up to +3% HP per 10 turns','Repair Speed bonus up to 40%','Impenetrable Armor bonus up to +40');
Admiral[13] = new Array('Famous Privateer', 'Races: Buckaneer', 'Stealth bonus up to 70%','Speed bonus up to 20%','Weak Cloaking (lvl 7+)','Privateer bonus up to +7');
Admiral[14] = new Array('Genetic Throwback', 'Races: Bosalian', 'Panic Modifier +5','PSI Attack bonus up to 100%','Weak Cloaking','Complete Cloaking (lvl 10+)','Weak Cloaking Detection (lvl 15+)');
Admiral[15] = new Array('Intuition', 'Races: Human', 'Critical Hit bonus up to 15%','Detection bonus up to +3','Interpretation bonus up to +9','Weak Cloaking Detection (lvl 5+)');
Admiral[16] = new Array('Irrational Tactics', 'Races: Human', 'Defense Rate bonus up to 30%','Armor Defense Rate bonus up to 20%','Maneuver penalty (up to lvl 19)');
Admiral[17] = new Array('Lone Wolf', 'Races: Human', 'Berserk Modifier +5','Stealth bonus up to 40%','Mobility bonus up to 40%','Weak Cloaking (lvl 6+)','Decreases morale gain from capital fleet by up to 100%','Decreases bonuses from Capital Fleet by 50% for the fleet one commands.','Privateer bonus up to +7');
Admiral[18] = new Array('Lying Dormant', 'Races: Agerus', 'Stealth up to 45%','Weak Cloaking','Decreases bonuses from Capital Fleet by 50% for the fleet one commands.');
Admiral[19] = new Array('Management Protocol', 'Races: Evintos', 'Fleet Commanding bonus up to +5','Speed bonus up to 50%','Attack Rating bonus up to 10%','PSI Defense bonus up to 40%');
Admiral[20] = new Array('Mental Giant', 'Races: Bosalian, Xeloss', 'Berserk Modifier -5','Panic Modifier -5','PSI Attack bonus up to 20%','PSI Defense penalty (up to lvl 10)','Weak Cloaking Detection (lvl 7+)','Complete Cloaking Detection (lvl 10+)','Detection bonus up to +10');
Admiral[21] = new Array('Meteor Drones', 'Races: Agerus', 'Damage reduction up to 20%','Maneuver penalty up to -4');
Admiral[22] = new Array('Missile Craters', 'Races: Agerus', 'Cooling Time reduction up to 35%','Armor Defense Rate penalty up to -30%','Damage penalty +10%');
Admiral[23] = new Array('Missile Specialist', 'Races: All', 'Missile Cooling Time reduction up to 40%','Missile Attack Rate bonus up to 40%','Missile Damage bonus up to 40%');
Admiral[24] = new Array('Pattern Broadcaster', 'Races: Tecanoid', 'Defense Rate Against Missiles bonus up to 60%');
Admiral[25] = new Array('Psychic Progenitor', 'Races: Xesperados', 'Berserk Modifier +5','PSI Attack bonus up to 100%','Complete Cloaking Detection','Weak Cloaking (lvl 7+)','Complete Cloaking (lvl 13+)','Efficiency penalty (up to lvl 17)');
Admiral[26] = new Array('Retreat Shield', 'Races: Bosalian', 'Berserk Modifier -5','Panic Modifier -10','Shield Solidity bonus up to +3','Shield Strength bonus up to 80%','Impenetrable Shield bonus up to 80%','Efficiency penalty up to -40%');
Admiral[27] = new Array('Rigid Thinking', 'Races: Evintos', 'Berserk Modifier -5','Panic Modifier -10','Defense Rate bonus up to 30%','Impeneterable Armor bonus up to 5%','PSI Defense bonus up to 100%');
Admiral[28] = new Array('Shield Disrupter', 'Races: Buckaneer', 'Shield Distortion bonus up to 90%','Attack Rate bonus up to 30%');
Admiral[29] = new Array('Shield System Specialist', 'Races: All', 'Shield Solidity bonus up to +5','Shield Strength bonus up to 50%','Shield Recharge Rate bonus up to 50%','Impenetrable Shield bonus up to 50%');
Admiral[30] = new Array('Tactical Genius', 'Races: Xerusian', 'Fleet morale bonus of 25','Damage reduction up to 15%','Impeneterable Armor bonus up to +5','Increases bonuses to others from Capital Fleet by 50% if commanding Capital Fleet','Panic Modifier -15','Berserk Modifier -5');
Admiral[31] = new Array('Trajectory Augmentation', 'Races: Tecanoid', 'Projectile Attack Rate bonus up to 30%','Projectile Damage bonus up to 10%','Efficiency bonus up to 30%');
Admiral[32] = new Array('Xenophobic Fanatic', 'Races: Xeloss', 'Berserk Modifier +5','Panic Modifier -5','PSI Attack bonus up to 50%','Mobility bonus up to 25%','PSI Defense penalty up to -20%','Siege/Blockade/Raid/Privateer bonus up to +5');

function admrlGenerateIDfromName(name)
{
    var retarray = new Array();
    var tempidArray = name.split(', ');
    
     for(var i = 0; i < Admiral.length; i++)
     {
         if(Admiral[i][0] == tempidArray[0])
         {
         	retarray[0] = i;
         	break;
         }
     }
    for(var i = 0; i < Admiral.length; i++)
     {
         if(Admiral[i][0] == tempidArray[1])
         {
         	retarray[1] = i;
         	break;
         }
     }
     return retarray;
}
function admrlGenerateTooltipText(id)
{
 var id1 = id[0];
 var id2 = id[1];
 var tempString = new String();
 for(var i = 0; i < Admiral[id1].length; i++)
 {
     if(i<1)
     {
	 tempString += '<span class="admrlTitle">'+Admiral[id1][i] + '</span><br/>';
     }
     else if(i<2)
     {
	 tempString += '<span class="admrlRace">'+Admiral[id1][i] + '</span><br/>';
     }
     else
     {
	 tempString += '<span class="admrlAttrib">'+Admiral[id1][i] + '</span><br/>';
     }
 }
 tempString += "<br/>";
for(var i = 0; i < Admiral[id2].length; i++)
 {
     if(i<1)
     {
	 tempString += '<span class="admrlTitle">'+Admiral[id2][i] + '</span><br/>';
     }
     else if(i<2)
     {
	 tempString += '<span class="admrlRace">'+Admiral[id2][i] + '</span><br/>';
     }
     else
     {
	 tempString += '<span class="admrlAttrib">'+Admiral[id2][i] + '</span><br/>';
     }
 }
 return tempString;
}
var ControlMod = new Array();
ControlMod[0] = new Array('Environment','Determines how much your planets have to be terraformed in order to achive the Suitable<br/> status (anything lower penalises overall production, growth and building rate of a given planet).');
ControlMod[1] = new Array('Production','Determines the amount of PP (Production Points) produced per planet.');
ControlMod[2] = new Array('Commerce','Affects the amount of PP your planets will get from commerce. Each commerce CM increases the<br/> production gain from each planet it is linked with by 0.8%. The base amount is 8%.');
ControlMod[3] = new Array('Diplomacy','Affects with the Empire (ie. bribery, boons), changes to Honor and Council Honor<br/>  (ifspeaker).');
ControlMod[4] = new Array('Growth','Determines the reproduction rate of your people.');
ControlMod[5] = new Array('Military','Determines the amount of MP (Military Points) produced per planet, the amount of experience<br/> given to newly formed fleets, the amount of experience gained in Training missions, the frequency of <br/>commander spawning and the amount of your total production (planet production + commerce - upkeep)<br/> that will be spent each turn on ship building (that PP will be spent no matter if you order ships to be built or not<br/> - in case there are no ships in the queue, the PP goes to the ship pool).' ,'Note: The maximum Military CM is 10. Anything higher gives only MP benefits.');
ControlMod[6] = new Array('Efficiency','Determines your Waste rate.','Note: The maximum Efficiency CM is 10. Anything <br/>higher gives no benefits.');
ControlMod[7] = new Array('Facility Cost','Determines the building rate and maximum building <br/>capacity of your planets. The higher the better.','Note: The maximum Facility Cost CM is 5. Anything higher gives no benefits.');
ControlMod[8] = new Array('Research','Determines the amount of RP (Research Points) produced per planet. Research CM also <br/>reduces the RP cost of techs while in free research');
ControlMod[9] = new Array('Spy','Determines the chance of your special ops to be successful, and decreases the chance of success <br/>of special ops targeted at you slightly (you need an increased security level to <br/>significantly reduce the chance of spy ops working on you).');
ControlMod[10] = new Array('Genius','Determines the occurence of good commanders versus bad.','Note: The maximum Genius CM is 15. Anything higher gives no benefits.');
ControlMod[11] = new Array('Factory','Factories are used to generate PP, which leads to higher shipbuilding.','PP per Factory: 60 + (Production CM × 10)');
ControlMod[12] = new Array('Military Base','Military bases are used to generate MP, which pays for fleet upkeep.','MP per Military Base: 10 + (Military CM × 2)');
ControlMod[13] = new Array('Research Lab','Research labs are used to generate RP, which leads to faster researching.','RP per Research Lab: (10 + Research CM)');

function cmGenerateIDfromName(name)
{
for(var i = 0; i < ControlMod.length; i++)
{
    if(ControlMod[i][0] == name)
	return i;
}
//failed to find the name
alert('failed to find ' + name);
return 0;
}
function cmGenerateTooltipText(id)
{
id--;
var tempString = new String();
for(var i = 0; i < ControlMod[id].length; i++)
{
    if(i<1)
    {
	tempString += '<span class="cmTitle">'+ControlMod[id][i] + '<span/><br/>';
    }
    else if( i == 1)
    {
	tempString += '<span class="cmElement">'+ControlMod[id][i] + '<span/><br/>';
    }
    else
    {
    	tempString += '<span class="cmNote">'+ControlMod[id][i] + '<span/><br/>';
    }

}
return tempString;
}
CommieStat = new Array();
CommieStat[0] = new Array('Class','Determines the bonuses that the commander gives to other fleets when he is the Armada Commander<br/> (capital fleet). In all other cases, it is irrelevant. Class D commander can be as good as Class A.');
CommieStat[1] = new Array('Fleet Commanding', 'Determines the maximum amount of ships you can fleet with a commander. The maximum value</br> for Level 1 admiral is 11, which becomes 40 at Level 20.');
CommieStat[2] = new Array('Efficiency', 'Determines how effectively your fleet targets the enemy fleet, spreading damage more<br/> throughout enemy fleets the lower your efficiency is. Additionally, it influences the ability to overcome various psychological effects</br> (ie. Berserk, Retreat, Panic). Note that it can much be higher than 100%.');
CommieStat[3] = new Array('Siege', 'Increases (value × 3%) Attack Rate in a Siege');
CommieStat[4] = new Array('Blockade', 'Increases (value × 3%) Attack Rate in a Blockade');
CommieStat[5] = new Array('Siege Repelling', 'Increases (value × 3%) Defense Rate in a Defending a Siege.');
CommieStat[6] = new Array('Break Blockade', 'Increases (value × 3%) Defense Rate in a Defending a Blockade.');
CommieStat[7] = new Array('Raid ', 'Increases chance of successful Raid');
CommieStat[8] = new Array('Privateer' ,'Increases chance of successful Privateer');
CommieStat[9] = new Array('Maneuver', 'Increases (value × 3%) ship Mobility and Speed.');
CommieStat[10] = new Array('Detection', 'Inarguably the most important ability, it determines the range at which a commander will see the enemy and fire. Also used to detect enemy Raids and Privateers.');
CommieStat[11] = new Array('Interpretation', 'Currently not implemented.');

function cstatGenerateIDfromName(name)
{
for(var i = 0; i < CommieStat.length; i++)
{
    if(CommieStat[i][0] == name)
	return i;
}
//failed to find the name
alert('failed to find ' + name);
return 0;
}
function cstatGenerateTooltipText(id)
{
var tempString = new String();
for(var i = 0; i < CommieStat[id].length; i++)
{
    if(i<1)
    {
	tempString += '<span class="cstatTitle">'+CommieStat[id][i] + '<span/><br/>';
    }
    else
    {
	tempString += '<span class="cstatElement">'+CommieStat[id][i] + '<span/><br/>';
    }

}
return tempString;
}