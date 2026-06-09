import org.archcave.classes.ACPlayers;
import org.archcave.classes.ACPlayersTable;
/**
 * Run with 'java TestAClass.class'
 *
 * @author Brian
 * @version 0.1 Nov 2, 2004 2:40:47 AM
 */
public class TestAClass {
    public static void main(String Args[])
    {
        System.out.println("Start Test...\nCreating playertable");
	ACPlayersTable aPlayerTable = new ACPlayersTable();
        ACPlayers.mTable = aPlayerTable;
	System.out.println("Creating player");
        ACPlayers aPlayer = new ACPlayers(2);
        System.out.println("Current Currency = " +  aPlayer.getCurrency());
        aPlayer.setCurrency(1000000);
        System.out.println("Current Currency = " +  aPlayer.getCurrency());
	System.out.println("Creating player via table");
	aPlayerTable.create();
	System.out.println("adding first created player to table");
	aPlayerTable.add(aPlayer);
        System.out.println("End Test...");
    }
}
