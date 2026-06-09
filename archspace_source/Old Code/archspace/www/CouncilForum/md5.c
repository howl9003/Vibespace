#include <stdio.h>
#include <stdlib.h>
#include <string.h>

int main(int argc, char *argv[])
{
  char *org = "55555";
  char str[256];
  char str2[256];
  char result[256];
  char cmp[256] = "c5fe25896e49ddfe996db7508cf00534";

  FILE *pipe_fp;

  sprintf(str, "md5 -q -s %s", org);

  pipe_fp = popen(str, "r");
  fgets(result, 256, pipe_fp);

  pclose(pipe_fp);

  sprintf(str2, "%s\n", cmp);

  printf("result[256] is\t%s", result);
  printf("cmp[256] is\t%s\n", cmp);
  printf("strcmp result is %d\n", strcmp(result, str2));

  return 1;
}
