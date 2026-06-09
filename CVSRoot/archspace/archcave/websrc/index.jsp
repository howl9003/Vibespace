<%@ page import="org.archcave.classes.ACUsersTable,
                 org.archcave.classes.ACUsers"%>
<jsp:useBean id="ACCore" class="org.archcave.beans.ACCore" scope="application" />
<HTML>
<HEAD>
</HEAD>
<BODY>
    <%
        String status;
        if (!ACCore.isRunning())
          status = "Down";
        else
          status = "Running";
    %>
    The Server is Currently: <%= status %>
    Users List:
    <%
        ACUsersTable aUserTable = ACCore.getUsersTable();
        for (int i=0; i < aUserTable.length(); i++)
        {
            ACUsers aUser = aUserTable.get(i);
            if (aUser != null)
            {
                out.println("User #"+(i+1)+": "+aUser.getName()+"<BR/>");
            }
        }
    %>
<h2>Welcome</h2>
</BODY>
</HTML>
