package com.adobe.stock.enums;

import java.util.HashMap;

import org.testng.Assert;
import org.testng.annotations.BeforeClass;
import org.testng.annotations.Listeners;
import org.testng.annotations.Test;

@Listeners({ com.adobe.stock.logger.TestCustomLogger.class,
        com.adobe.stock.logger.TestSuiteLogger.class })
@Test(suiteName = "Asset3DType")
public class Asset3DTypeTest {
    private HashMap<Asset3DType, String> testData = new HashMap<Asset3DType, String>();

    @BeforeClass
    public void initializeTestData() {
        testData.put(Asset3DType.MODELS, "1");
        testData.put(Asset3DType.LIGHTS, "2");
        testData.put(Asset3DType.MATERIALS, "3");
    }

    @Test(groups = "Asset3DType.toString")
    public void toString_should_return_string_equivalent_value_of_Asset3DType_enum() {
        for (Asset3DType env : Asset3DType.values()) {
            Asset3DType.valueOf(env.name());
            Assert.assertEquals(env.toString(), testData.get(env));
        }
    }
}
