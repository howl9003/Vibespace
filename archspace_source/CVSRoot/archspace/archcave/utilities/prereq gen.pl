#!/usr/bin/perl

$DEF = "../src/org/data/prerequisite.types.def";
$OUTDIR = "../src/org/data/";

open(FILEREAD, "< ".$DEF);
@lines = <FILEREAD>;
close(FILEREAD);

my $eType;
my $eNum;
my $switch;
my $description_switch;
my $index = 0;

foreach(@lines) {
	if (length($_) > 2) {
		my @definition = split(/,/, $_);
		my $name = @definition[0];
		$name =~ s/^\s+//; ## Remove leading white spaces.
		$name =~ s/\s+$//; ## Remove trailing white spaces.
		my $action = @definition[1];
		$action =~ s/^\s+//; ## Remove leading white spaces.
		$action =~ s/\s+$//; ## Remove trailing white spaces.
		my $args = "";
		if (@definition > 2) {
			$args = @definition[2];
		}
		my $ename = uc "E_".$name;
		$eType = $eType.'		"'.$name."\",\n";
		$eNum = $eNum."	public static final int ".$ename." = ".$index.";\n";
		if (-1 < (index lc $args, 'boolean_only')) {
			if (-1 < (index lc $args, 'raw_eval')) {
				$switch = $switch."		case ".$ename.":
				switch(mOperator) {
					case E_NOT:
						if (!".$action.") return true;
						return false;
					case E_EQUAL:
						if (".$action.") return true;
						return false;
				}
				return false;\n";
			} else {
				$switch = $switch."		case ".$ename.":
				switch(mOperator) {
					case E_NOT:
						if (mValue != ".$action.") return true;
						return false;
					case E_EQUAL:
						if (mValue == ".$action.") return true;
						return false;
				}
				return false;\n";
			}
		} else {
			$switch = $switch."		case ".$ename.":
				switch(mOperator) {
					case E_NOT:
						if (mValue != ".$action.") return true;
						return false;
					case E_EQUAL:
						if (mValue == ".$action.") return true;
						return false;
					case E_LESS_THEN:
						if (mValue > ".$action.") return true;
						return false;
					case E_LESS_THEN_OR_EQUAL:
						if (mValue >= ".$action.") return true;
						return false;
					case E_GREATER_THEN_OR_EQUAL:
						if (mValue <= ".$action.") return true;
						return false;
					case E_GREATER_THEN:
						if (mValue < ".$action.") return true;
						return false;
				}
				return false;\n";
		}
		$index++;
	}
}

open(FILEWRITE, "> ".$OUTDIR."prerequisite.eTypes.out");
print FILEWRITE $eType;
close(FILEWRITE);
print $eType;

open(FILEWRITE, "> ".$OUTDIR."prerequisite.enums.out");
print FILEWRITE $eNum;
close(FILEWRITE);
print $eNum;

open(FILEWRITE, "> ".$OUTDIR."prerequisite.switch.out");
print FILEWRITE $switch;
close(FILEWRITE);
print $switch;
