#include <stdio.h>
#include <time.h>
#include <stdlib.h>

int haha(int a);

int
main()
{
	long long int
		Int = 2100000000;
	unsigned long long int
		UnsignedInt = 2100000000;
	printf("%d, %d\n", Int, UnsignedInt);
	Int *= Int * 4;
	UnsignedInt *= UnsignedInt * 4;
	printf("%lld, %llu\n", Int, UnsignedInt);

	long long int
		result;
	int
		temp = 2100000000;
	result = (long long int)temp * 5;
	printf("%lld\n", result);

	long long int
		factorial = 1;
	for (int i=1 ; i <= 40 ; i++)
	{
		factorial *= i;
	}
	printf("factorial = %d\n", factorial);

	printf("%d, %d\n", sizeof(float), sizeof(double));
	printf("%d\n", haha(factorial));

	time_t
		haha = -1;
	printf("%ld\n", haha);

	printf("\nresult = %d\n", system("sed s/printf/PRINTF test.cc"));
	printf("result = %d\n", system("sedd s/printf/PRINTF test.cc"));
	printf("result = %d\n", system("sed s/printf/PRINTF/ test.cc"));
	printf("result = %d\n", system("sed s/dummy/dummy/g /dev/null"));
	printf("result = %d\n", system("cp /dev/null ~/haha"));
	printf("result = %d\n", system("rm ~/haha"));
}

int
haha(int a)
{
	return a;
}
